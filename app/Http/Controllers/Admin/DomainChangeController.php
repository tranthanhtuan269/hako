<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DomainContentReplacer;
use App\Support\EnvDomainUpdater;
use App\Support\SiteDomainMigrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainChangeController extends Controller
{
    public function index(): View
    {
        return view('admin.domain-change.index', [
            'currentDomain' => (string) config('site.domain'),
            'currentSiteUrl' => (string) config('site.url'),
            'currentAppUrl' => (string) config('app.url'),
        ]);
    }

    public function store(Request $request, SiteDomainMigrator $migrator): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'old_domain' => ['required', 'string', 'max:253'],
            'new_domain' => ['required', 'string', 'max:253'],
        ]);

        $oldDomain = DomainContentReplacer::normalizeHost($validated['old_domain']);
        $newDomain = DomainContentReplacer::normalizeHost($validated['new_domain']);

        $error = null;

        if (! DomainContentReplacer::isValidHost($oldDomain)) {
            $error = 'Domain hiện tại không hợp lệ.';
        } elseif (! DomainContentReplacer::isValidHost($newDomain)) {
            $error = 'Domain mới không hợp lệ.';
        } elseif ($oldDomain === $newDomain) {
            $error = 'Domain cũ và domain mới phải khác nhau.';
        }

        if ($error !== null) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $error], 422);
            }

            return redirect()
                ->route('admin.domain-change.index')
                ->withInput()
                ->with('error', $error);
        }

        $summary = $migrator->migrate($oldDomain, $newDomain);
        $envResult = EnvDomainUpdater::update($newDomain);

        $message = $summary['total_replacements'] > 0
            ? "Đã đổi domain từ {$oldDomain} sang {$newDomain} trong toàn bộ content (link và text)."
            : "Quét xong. Không tìm thấy “{$oldDomain}” trong blogs/stores/coupons.";

        $message .= ' '.$envResult['message'];

        if ($envResult['updated']) {
            $message .= ' Chạy `php artisan config:clear` nếu config đang bị cache.';
        }

        $detail = sprintf(
            'Blogs: %d/%d bản ghi (%d chỗ). Stores: %d/%d bản ghi (%d chỗ). Coupons: %d/%d bản ghi (%d chỗ).',
            $summary['posts']['updated'],
            $summary['posts']['scanned'],
            $summary['posts']['replacements'],
            $summary['stores']['updated'],
            $summary['stores']['scanned'],
            $summary['stores']['replacements'],
            $summary['coupons']['updated'],
            $summary['coupons']['scanned'],
            $summary['coupons']['replacements'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'detail' => $detail,
                'summary' => $summary,
                'env' => $envResult,
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
            ]);
        }

        return redirect()
            ->route('admin.domain-change.index')
            ->with('success', $message.' '.$detail);
    }
}
