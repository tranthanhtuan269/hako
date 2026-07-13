<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DomainContentReplacer;
use App\Support\SiteDomainMigrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainChangeController extends Controller
{
    public function store(Request $request, SiteDomainMigrator $migrator): JsonResponse
    {
        $validated = $request->validate([
            'old_domain' => ['required', 'string', 'max:253'],
            'new_domain' => ['required', 'string', 'max:253'],
        ]);

        $oldDomain = DomainContentReplacer::normalizeHost($validated['old_domain']);
        $newDomain = DomainContentReplacer::normalizeHost($validated['new_domain']);

        if (! DomainContentReplacer::isValidHost($oldDomain)) {
            return response()->json([
                'ok' => false,
                'message' => 'Old domain is not valid.',
            ], 422);
        }

        if (! DomainContentReplacer::isValidHost($newDomain)) {
            return response()->json([
                'ok' => false,
                'message' => 'New domain is not valid.',
            ], 422);
        }

        if ($oldDomain === $newDomain) {
            return response()->json([
                'ok' => false,
                'message' => 'Old and new domain must be different.',
            ], 422);
        }

        $summary = $migrator->migrate($oldDomain, $newDomain);

        return response()->json([
            'ok' => true,
            'message' => $summary['total_replacements'] > 0
                ? 'Domain links updated across site content.'
                : 'Scan complete. No matching links were found.',
            'summary' => $summary,
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain,
        ]);
    }
}
