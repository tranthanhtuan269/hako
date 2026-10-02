<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\SiteEditorsPicks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditorsPicksController extends Controller
{
    public function index(): View
    {
        $selectedStores = SiteEditorsPicks::selectedStores();
        $selectedIds = $selectedStores->pluck('id')->all();

        // If no store configured yet, load current default home stores for easy editing
        if ($selectedStores->isEmpty()) {
            $defaultStores = SiteEditorsPicks::forHome(5);
            $selectedStores = $defaultStores;
            $selectedIds = $defaultStores->pluck('id')->all();
        }

        $availableStores = Store::active()
            ->whereNotIn('id', $selectedIds)
            ->orderBy('name')
            ->get();

        return view('admin.editors-picks.index', compact('selectedStores', 'availableStores'));
    }

    public function update(Request $request): RedirectResponse
    {
        $ids = $request->input('store_ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        SiteEditorsPicks::setStoreIds((array) $ids);

        return redirect()
            ->route('admin.editors-picks.index')
            ->with('success', 'Editor’s Picks stores saved successfully.');
    }
}
