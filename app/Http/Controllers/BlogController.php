<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::published()
            ->when($request->get('q'), function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'q' => trim($request->get('q', '')),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $post->incrementViews();

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
