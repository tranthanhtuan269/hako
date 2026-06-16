<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TrackingScripts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(): View
    {
        $rules = TrackingScripts::conversionRules();

        if ($rules === []) {
            $rules = [['path' => '', 'html' => '', 'send_to' => '']];
        }

        return view('admin.tracking.index', [
            'trackingHead' => TrackingScripts::headHtml(),
            'conversionRules' => $rules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_head' => ['nullable', 'string', 'max:20000'],
            'conversion_rules' => ['nullable', 'array'],
            'conversion_rules.*.path' => ['nullable', 'string', 'max:500'],
            'conversion_rules.*.html' => ['nullable', 'string', 'max:20000'],
        ]);

        TrackingScripts::setHeadHtml($validated['tracking_head'] ?? null);
        TrackingScripts::setConversionRules($validated['conversion_rules'] ?? []);

        return redirect()
            ->route('admin.tracking.index')
            ->with('success', 'Tracking scripts saved.');
    }
}
