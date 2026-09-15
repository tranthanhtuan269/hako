<?php

namespace App\Http\Controllers;

use App\Support\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function landing(string $code, Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $referrer = $affiliate->findReferrerByCode($code);

        if ($referrer) {
            $cookieName = config('affiliate.cookie_name', 'affiliate_ref');
            $days = (int) config('affiliate.cookie_days', 30);

            cookie()->queue(cookie(
                $cookieName,
                $referrer->referral_code,
                $days * 24 * 60,
                '/',
                null,
                false,
                false,
                false,
                'lax'
            ));

            $affiliate->trackVisit(
                $referrer,
                $request->ip(),
                $request->userAgent(),
                $request->fullUrl()
            );
        }

        return redirect()->route('login')->with(
            'info',
            $referrer
                ? 'You were referred by ' . $referrer->name . '. Sign in to continue.'
                : 'Invalid referral link. Please sign in.'
        );
    }
}
