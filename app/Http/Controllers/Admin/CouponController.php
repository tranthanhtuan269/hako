<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::with(['store.category'])->latest()->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $stores = Store::orderBy('name')->get();

        return view('admin.coupons.form', [
            'coupon' => new Coupon(),
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        $stores = Store::orderBy('name')->get();

        return view('admin.coupons.form', compact('coupon', 'stores'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request);
        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = filled($data['code'] ?? null) ? trim($data['code']) : null;
        $data['type'] = filled($data['code']) ? 'coupon' : 'discount';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);

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
