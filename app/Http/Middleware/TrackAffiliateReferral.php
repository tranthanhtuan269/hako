<?php

namespace App\Http\Middleware;

use App\Support\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function __construct(private AffiliateService $affiliate) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('affiliate.enabled')) {
            return $next($request);
        }

        $code = $request->query('ref');

        if (filled($code)) {
            $referrer = $this->affiliate->findReferrerByCode($code);

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

                $this->affiliate->trackVisit(
                    $referrer,
                    $request->ip(),
                    $request->userAgent(),
                    $request->fullUrl()
                );
            }
        }

        return $next($request);
    }
}
