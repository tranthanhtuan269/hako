<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\PublicImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $query = Post::query()->orderByDesc('is_pinned_home')->orderByDesc('home_pin_sort_order')->orderByDesc('created_at');

        if ($q !== '') {
            $query->where('title', 'like', '%'.$q.'%');
        }

        $posts = $query->paginate(20)->withQueryString();

        return view('admin.posts.index', compact('posts', 'q'));
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['featured_image'] = $this->resolveFeaturedImage($request, $data['featured_image'] ?? null);
        $data['user_id'] = auth()->id();

        Post::create($data);

        return redirect()->route('admin.posts.index')->with('success', 'Blog post created successfully.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validated($request);
        $data['featured_image'] = $this->resolveFeaturedImage(
            $request,
            $request->input('featured_image'),
            $post->featured_image
        );
        $post->update($data);

        return redirect()->route('admin.posts.index')->with('success', 'Blog post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        PublicImage::delete($post->featured_image);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Blog post deleted successfully.');
    }

    public function toggleHomePin(Post $post): RedirectResponse
    {
        if ($post->is_pinned_home) {
            $post->update([
                'is_pinned_home' => false,
                'home_pin_sort_order' => 0,
            ]);

            return back()->with('success', 'Post unpinned from homepage.');
        }

        $nextOrder = (int) Post::query()->pinnedHome()->max('home_pin_sort_order') + 1;

        $post->update([
            'is_pinned_home' => true,
            'home_pin_sort_order' => $nextOrder,
        ]);

        return back()->with('success', 'Post pinned to homepage.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'featured_image_file' => ['nullable', 'image', 'max:5120'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        if (blank($data['author_name'] ?? null)) {
            unset($data['author_name']);
        }

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        unset($data['featured_image_file']);

        return $data;
    }

    private function resolveFeaturedImage(Request $request, ?string $imageUrl, ?string $existing = null): ?string
    {
        if ($request->hasFile('featured_image_file')) {
            PublicImage::delete($existing);

            return PublicImage::store($request->file('featured_image_file'), 'posts');
        }

        if (filled($imageUrl)) {
            if ($existing && PublicImage::isStored($existing) && $imageUrl !== $existing) {
                PublicImage::delete($existing);
            }

            $userId = auth()->id() ?? 'admin';

            return PublicImage::storeBlogFeaturedFromRemote($imageUrl, $userId)
                ?? PublicImage::ingestRemote($imageUrl, "posts/{$userId}");
        }

        return $existing;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (Post::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
