<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()
            ->withCount(['coupons' => fn ($q) => $q->valid()])
            ->orderBy('sort_order')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function show(string $slug): View
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $coupons = $category->coupons()
            ->with('store')
            ->valid()
            ->latest()
            ->paginate(16);

        return view('categories.show', compact('category', 'coupons'));
    }
}
