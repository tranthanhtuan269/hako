<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateSignupRegistration;
use App\Support\CouponSpeakClient;
use App\Support\SiteIntegrations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateSignupController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request, CouponSpeakClient $scan): View
    {
        $search = $this->searchQuery($request);
        $result = $this->fetchPage($scan, 1, $search);

        return view('admin.affiliate-signups.index', [
            'projects' => $result['projects'],
            'pagination' => $result['pagination'],
            'search' => $search,
            'error' => $result['error'],
            'scanConfigured' => filled(SiteIntegrations::scanAffiliateSignupsApiUrl()),
            'scanSite' => SiteIntegrations::scanSite(),
            'scanApiUrl' => SiteIntegrations::scanAffiliateSignupsApiUrl(),
            'feedUrl' => route('admin.affiliate-signups.feed'),
        ]);
    }

    public function feed(Request $request, CouponSpeakClient $scan): JsonResponse
    {
        $search = $this->searchQuery($request);
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->fetchPage($scan, $page, $search);

        if ($result['error']) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'html' => view('admin.affiliate-signups._rows', [
                'projects' => $result['projects'],
            ])->render(),
            'page' => $result['pagination']['page'],
            'has_more' => $result['pagination']['page'] < $result['pagination']['total_pages'],
            'total' => $result['pagination']['total'],
        ]);
    }

    public function register(Request $request, int $project): JsonResponse
    {
        $validated = $request->validate([
            'signup_link' => ['required', 'url', 'max:512'],
            'project' => ['nullable', 'string', 'max:255'],
        ]);

        $registration = AffiliateSignupRegistration::markRegistered(
            $project,
            $validated['signup_link'],
            $validated['project'] ?? null,
            auth()->id()
        );

        return response()->json([
            'ok' => true,
            'registered_at' => $registration->registered_at?->format('Y-m-d H:i'),
        ]);
    }

    private function searchQuery(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    /** @return array{projects: list<array<string, mixed>>, pagination: array<string, int>, error: ?string} */
    private function fetchPage(CouponSpeakClient $scan, int $page, string $search): array
    {
        $result = $scan->fetchAffiliateSignups(
            $page,
            self::PER_PAGE,
            $search !== '' ? $search : null
        );

        $registered = AffiliateSignupRegistration::registeredMap();

        $result['projects'] = collect($result['projects'])
            ->map(function (array $project) use ($registered) {
                $id = (int) ($project['id'] ?? 0);
                $project['is_registered'] = isset($registered[$id]);
                $project['registered_at'] = $registered[$id] ?? null;

                return $project;
            })
            ->values()
            ->all();

        return $result;
    }
}
