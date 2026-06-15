<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuthorProfile;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $defaultAuthor = AuthorProfile::default();

        $authors = User::query()
            ->whereNotNull('author_slug')
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => AuthorProfile::fromUser($user));

        return view('authors.index', compact('defaultAuthor', 'authors'));
    }

    public function show(string $slug): View
    {
        $default = AuthorProfile::default();

        if ($slug === $default->slug) {
            $author = $default;
        } else {
            $user = User::query()
                ->where('author_slug', $slug)
                ->whereHas('posts', fn ($query) => $query->published())
                ->firstOrFail();

            $author = AuthorProfile::fromUser($user);
        }

        $posts = $author->publishedPostsQuery()->with('user')->paginate(12);

        return view('authors.show', compact('author', 'posts'));
    }
}
