<?php

namespace App\Support;

use App\Models\AffiliateOrder;
use App\Models\AffiliatePayoutRequest;
use App\Models\AffiliateVisitLog;
use App\Models\User;
use App\Notifications\AffiliatePayoutRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class AffiliateService
{
    public function referralUrl(User $user): string
    {
        return route('referral.landing', ['code' => $user->referral_code]);
    }

    public function findReferrerByCode(?string $code): ?User
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return User::query()->where('referral_code', $code)->first();
    }

    public function trackVisit(User $referrer, ?string $ip, ?string $userAgent, ?string $landingUrl): void
    {
        AffiliateVisitLog::create([
            'referrer_user_id' => $referrer->id,
            'ip_address' => $ip,
            'user_agent' => Str::limit((string) $userAgent, 500, ''),
            'landing_url' => Str::limit((string) $landingUrl, 500, ''),
            'created_at' => now(),
        ]);
    }

    public function attachReferrerToNewUser(User $user, ?string $referralCode): void
    {
        $referrer = $this->findReferrerByCode($referralCode);

        if (! $referrer || $referrer->id === $user->id) {
            return;
        }

        $user->forceFill(['referred_by_user_id' => $referrer->id])->save();
    }

    public function ensureReferralCode(User $user): string
    {
        if (filled($user->referral_code)) {
            return $user->referral_code;
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        $user->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'AFF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (AffiliateOrder::query()->where('order_number', $number)->exists());

        return $number;
    }

    public function calculateCommission(float $amount, float $rate): float
    {
        return round($amount * ($rate / 100), 2);
    }

    public function applyOrderStatusChange(AffiliateOrder $order, string $newStatus): void
    {
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            $order->refresh();
            $referrer = $order->referrer()->lockForUpdate()->first();

            if (! $referrer) {
                return;
            }

            if ($newStatus === AffiliateOrder::STATUS_PAID && ! $order->commission_credited) {
                $commission = $this->calculateCommission((float) $order->amount, (float) $order->commission_rate);
                $order->commission_amount = $commission;
                $order->commission_credited = true;
                $order->paid_at = now();
                $referrer->affiliate_balance = round((float) $referrer->affiliate_balance + $commission, 2);
                $referrer->save();
            }

            if (in_array($newStatus, [AffiliateOrder::STATUS_CANCELLED, AffiliateOrder::STATUS_REFUNDED], true)
                && $order->commission_credited) {
                $referrer->affiliate_balance = max(
                    0,
                    round((float) $referrer->affiliate_balance - (float) $order->commission_amount, 2)
                );
                $referrer->save();
                $order->commission_credited = false;
                $order->paid_at = null;
            }

            if ($newStatus === AffiliateOrder::STATUS_PENDING && $oldStatus === AffiliateOrder::STATUS_PAID && $order->commission_credited) {
                $referrer->affiliate_balance = max(
                    0,
                    round((float) $referrer->affiliate_balance - (float) $order->commission_amount, 2)
                );
                $referrer->save();
                $order->commission_credited = false;
                $order->paid_at = null;
                $order->commission_amount = 0;
            }

            $order->status = $newStatus;
            $order->save();
        });
    }

    public function createPayoutRequest(User $user, array $data): AffiliatePayoutRequest
    {
        $amount = round((float) $data['amount'], 2);
        $min = (float) config('affiliate.min_payout_amount', 50);

        if ($amount < $min) {
            throw new \InvalidArgumentException("Minimum payout amount is {$min}.");
        }

        if ($amount > (float) $user->affiliate_balance) {
            throw new \InvalidArgumentException('Requested amount exceeds your available balance.');
        }

        return DB::transaction(function () use ($user, $data, $amount) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($amount > (float) $locked->affiliate_balance) {
                throw new \InvalidArgumentException('Requested amount exceeds your available balance.');
            }

            $locked->affiliate_balance = round((float) $locked->affiliate_balance - $amount, 2);
            $locked->save();

            $request = AffiliatePayoutRequest::create([
                'user_id' => $locked->id,
                'amount' => $amount,
                'status' => AffiliatePayoutRequest::STATUS_PENDING,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_details' => $data['payment_details'] ?? null,
                'member_note' => $data['member_note'] ?? null,
            ]);

            $this->notifyAdminsOfPayoutRequest($request);

            return $request;
        });
    }

    public function updatePayoutStatus(AffiliatePayoutRequest $request, string $status, ?string $adminNote, User $admin): void
    {
        if (! in_array($status, AffiliatePayoutRequest::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid payout status.');
        }

        DB::transaction(function () use ($request, $status, $adminNote, $admin) {
            $request->refresh();
            $oldStatus = $request->status;

            if ($oldStatus === $status) {
                return;
            }

            if ($status === AffiliatePayoutRequest::STATUS_REJECTED
                && in_array($oldStatus, [AffiliatePayoutRequest::STATUS_PENDING, AffiliatePayoutRequest::STATUS_APPROVED], true)) {
                $user = User::query()->whereKey($request->user_id)->lockForUpdate()->firstOrFail();
                $user->affiliate_balance = round((float) $user->affiliate_balance + (float) $request->amount, 2);
                $user->save();
            }

            $request->status = $status;
            $request->admin_note = $adminNote;
            $request->processed_at = in_array($status, [
                AffiliatePayoutRequest::STATUS_PAID,
                AffiliatePayoutRequest::STATUS_REJECTED,
            ], true) ? now() : null;
            $request->processed_by_user_id = $admin->id;
            $request->save();
        });
    }

    private function notifyAdminsOfPayoutRequest(AffiliatePayoutRequest $request): void
    {
        $request->load('user');

        $admins = User::query()->where('is_admin', true)->get();
        $message = sprintf(
            'Payout request #%d from %s for %s %s',
            $request->id,
            $request->user->name,
            number_format((float) $request->amount, 2),
            config('affiliate.currency', 'USD')
        );

        Log::channel('single')->info('[Affiliate] ' . $message, [
            'payout_request_id' => $request->id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
        ]);

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AffiliatePayoutRequested($request));
        }
    }
}
