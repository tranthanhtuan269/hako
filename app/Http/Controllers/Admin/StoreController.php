<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ValidatesStoreInput;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Support\PublicImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    use ValidatesStoreInput;

    public function index(): View
    {
        $stores = Store::withCount('coupons')->orderBy('sort_order')->paginate(20);

        return view('admin.stores.index', compact('stores'));
    }

    public function create(): View
    {
        return view('admin.stores.form', [
            'store' => new Store(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedStore($request);
        $data['logo'] = $this->resolveLogo($request, $data['logo'] ?? null);

        $store = Store::create($data);
        $store->ensureLogoStored($request->input('logo'));

        return redirect()->route('admin.stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(Store $store): View
    {
        return view('admin.stores.form', [
            'store' => $store,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $this->validatedStore($request, $store);
        $data['logo'] = $this->resolveLogo($request, $request->input('logo'), $store->logo, $store);

        $store->update($data);
        $store->ensureLogoStored($request->input('logo'));

        return redirect()->route('admin.stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        PublicImage::delete($store->logo);
        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Store deleted successfully.');
    }

    private function resolveLogo(Request $request, ?string $logoUrl, ?string $existing = null, ?Store $store = null): ?string
    {
        $userId = $store?->user_id ?? auth()->id() ?? 'admin';

        if ($request->hasFile('logo_file')) {
            PublicImage::delete($existing);

            return PublicImage::storeForUser($request->file('logo_file'), $userId);
        }

        if (filled($logoUrl)) {
            if ($existing && PublicImage::isStored($existing) && $logoUrl !== $existing) {
                PublicImage::delete($existing);
            }

            $stored = PublicImage::ingestRemote($logoUrl, "stores/{$userId}/logos");

            if ($stored) {
                return $stored;
            }
        }

        if ($existing && PublicImage::isValidImage($existing)) {
            return $existing;
        }

        return null;
    }
}
