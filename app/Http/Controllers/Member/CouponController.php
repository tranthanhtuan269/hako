<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::with(['store.category'])
            ->ownedBy(auth()->id())
            ->latest()
            ->paginate(20);

        return view('member.coupons.index', compact('coupons'));
    }

    public function create(): View|RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $stores = Store::ownedBy(auth()->id())->orderBy('name')->get();

        if ($stores->isEmpty()) {
            return redirect()
                ->route('member.stores.create')
                ->with('error', 'Create a store before adding coupons.');
        }

        return view('member.coupons.form', [
            'coupon' => new Coupon(),
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['user_id'] = auth()->id();

        Coupon::create($data);

        return redirect()->route('member.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        $stores = Store::ownedBy(auth()->id())->orderBy('name')->get();

        return view('member.coupons.form', compact('coupon', 'stores'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $data = $this->validated($request);
        $coupon->update($data);

        return redirect()->route('member.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()->route('member.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'store_id' => [
                'required',
                Rule::exists('stores', 'id')->where(fn ($q) => $q->where('user_id', auth()->id())),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'show_on_coupons' => ['boolean'],
            'coupons_sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['code'] = filled($data['code'] ?? null) ? trim($data['code']) : null;
        $data['type'] = filled($data['code']) ? 'coupon' : 'discount';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_on_coupons'] = $request->boolean('show_on_coupons', true);
        $data['coupons_sort_order'] = (int) ($data['coupons_sort_order'] ?? 0);
        $data['expires_at'] = filled($data['expires_at'] ?? null) ? $data['expires_at'] : null;

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (Coupon::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
