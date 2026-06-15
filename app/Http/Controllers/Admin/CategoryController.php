<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\CategoryIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['stores', 'coupons'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'iconOptions' => CategoryIcons::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'iconOptions' => CategoryIcons::options(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $data['slug'] = $this->uniqueSlug(
            $data['slug'] ?? $data['name'],
            $category->id
        );

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->stores()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Cannot delete this category while stores are still assigned to it. Edit those stores first.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'icon' => ['nullable', 'string', Rule::in(CategoryIcons::values())],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['slug'] = filled($data['slug'] ?? null) ? Str::slug($data['slug']) : null;
        $data['icon'] = filled($data['icon'] ?? null) ? $data['icon'] : null;

        return $data;
    }

    private function uniqueSlug(string $nameOrSlug, ?int $ignoreId = null): string
    {
        $slug = Str::slug($nameOrSlug);
        $original = $slug;
        $i = 1;

        while (Category::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
