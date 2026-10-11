<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()
            ->withCount([
                'coupons' => fn ($q) => $q->valid(),
                'stores' => fn ($q) => $q->active(),
            ])
            ->orderBy('sort_order')
            ->get();

        return view(ThemeManager::view('categories.index'), compact('categories'));
    }

    public function show(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type');
        if (! in_array($type, ['coupon', 'discount'], true)) {
            $type = null;
        }

        $coupons = $category->coupons()
            ->with('store')
            ->valid()
            ->ofType($type)
            ->latest()
            ->paginate(16)
            ->withQueryString();

        $stores = $category->stores()
            ->active()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->with(['coupons' => fn ($cq) => $cq->valid()
                ->where('show_on_store', true)
                ->select(['id', 'store_id', 'title', 'type', 'discount_type', 'discount_value', 'is_active', 'expires_at'])])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(ThemeManager::view('categories.show'), compact('category', 'coupons', 'stores', 'type', 'q'));
    }
}
