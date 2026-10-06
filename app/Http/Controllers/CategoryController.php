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

        return view(ThemeManager::view('categories.show'), compact('category', 'coupons', 'type'));
    }
}
