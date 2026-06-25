<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Concerns\ValidatesStoreInput;
use App\Http\Controllers\Concerns\SyncsStoreCouponDisplay;
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
    use SyncsStoreCouponDisplay;

    public function index(): View
    {
        $this->authorize('viewAny', Store::class);

        $stores = Store::ownedBy(auth()->id())
            ->with('category')
            ->withCount('coupons')
            ->latest()
            ->paginate(20);

        return view('member.stores.index', compact('stores'));
    }

    public function create(): View
    {
        $this->authorize('create', Store::class);

        return view('member.stores.form', [
            'store' => new Store(),
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Store::class);

        $data = $this->validatedStore($request);
        unset($data['sort_order'], $data['show_on_stores'], $data['stores_list_sort_order']);
        $data['logo'] = $this->resolveLogo($request, $data['logo'] ?? null, null, auth()->id());
        $data['user_id'] = auth()->id();

        $store = Store::create($data);
        $store->ensureLogoStored($request->input('logo'));

        return redirect()->route('member.stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(Store $store): View
    {
        $this->authorize('update', $store);

        return view('member.stores.form', [
            'store' => $store,
            'categories' => Category::active()->orderBy('name')->get(),
            'storeCoupons' => $this->storeCouponsForDisplayForm($store),
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        $data = $this->validatedStore($request, $store);
        unset($data['sort_order'], $data['show_on_stores'], $data['stores_list_sort_order']);
        $data['logo'] = $this->resolveLogo($request, $request->input('logo'), $store->logo, $store->user_id ?? auth()->id());

        $store->update($data);
        $store->ensureLogoStored($request->input('logo'));
        $this->syncStoreCouponDisplay($store, $request);

        return redirect()->route('member.stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        PublicImage::delete($store->logo);
        $store->delete();

        return redirect()->route('member.stores.index')->with('success', 'Store deleted successfully.');
    }

    private function resolveLogo(Request $request, ?string $logoUrl, ?string $existing = null, int|string|null $userId = null): ?string
    {
        $userId = $userId ?? auth()->id();

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
