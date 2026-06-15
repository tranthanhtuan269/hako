<?php

namespace App\Notifications;

use App\Models\AffiliatePayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AffiliatePayoutRequested extends Notification
{
    use Queueable;

    public function __construct(public AffiliatePayoutRequest $payoutRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->payoutRequest->loadMissing('user');
        $currency = config('affiliate.currency', 'USD');

        return (new MailMessage)
            ->subject('New affiliate payout request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($request->user->name . ' submitted a commission payout request.')
            ->line('Amount: ' . number_format((float) $request->amount, 2) . ' ' . $currency)
            ->line('Payment method: ' . ($request->payment_method ?: 'Not specified'))
            ->action('Review payout requests', route('admin.affiliate.payouts.index'))
            ->line('Please process the payment and update the request status in the admin panel.');
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->payoutRequest->loadMissing('user');

        return [
            'type' => 'affiliate_payout_requested',
            'payout_request_id' => $request->id,
            'user_id' => $request->user_id,
            'user_name' => $request->user->name,
            'amount' => $request->amount,
            'message' => $request->user->name . ' requested a payout of ' . number_format((float) $request->amount, 2),
        ];
    }
}
