<?php

namespace App\Support;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class AuthorProfile
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $title = null,
        public ?string $bio = null,
        public ?string $avatarUrl = null,
        public ?User $user = null,
        public bool $isDefault = false,
    ) {}

    public function url(): string
    {
        return route('authors.show', $this->slug);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }

        return strtoupper(mb_substr($this->name, 0, 2));
    }

    public static function default(): self
    {
        $config = config('site.default_author');

        return new self(
            name: $config['name'],
            slug: $config['slug'],
            title: $config['title'] ?? null,
            bio: $config['bio'] ?? null,
            avatarUrl: ! empty($config['avatar']) ? asset($config['avatar']) : null,
            isDefault: true,
        );
    }

    public static function fromUser(User $user): self
    {
        $user->ensureAuthorSlug();

        return new self(
            name: $user->name,
            slug: (string) $user->author_slug,
            title: $user->author_title ?: config('site.default_author.member_title'),
            bio: $user->author_bio ?: self::defaultMemberBio($user->name),
            avatarUrl: $user->authorAvatarUrl(),
            user: $user,
        );
    }

    public static function forPost(Post $post): self
    {
        if ($post->user_id && $post->user) {
            return self::fromUser($post->user);
        }

        $default = self::default();

        if (filled($post->author_name) && $post->author_name !== $default->name) {
            return new self(
                name: $post->author_name,
                slug: Str::slug($post->author_name),
                title: config('site.default_author.guest_title'),
                bio: self::defaultMemberBio($post->author_name),
            );
        }

        return $default;
    }

    public static function defaultMemberBio(string $name): string
    {
        return "{$name} writes about verified coupon codes, online deals, and practical savings tips for U.S. shoppers at " . config('site.name') . '.';
    }

    public function publishedPostCount(): int
    {
        if ($this->isDefault) {
            return Post::published()
                ->where(function ($query) {
                    $query->whereNull('user_id')
                        ->orWhere('author_name', $this->name);
                })
                ->count();
        }

        if ($this->user) {
            return $this->user->posts()->published()->count();
        }

        return Post::published()->where('author_name', $this->name)->count();
    }

    public function publishedPostsQuery()
    {
        if ($this->isDefault) {
            return Post::published()
                ->where(function ($query) {
                    $query->whereNull('user_id')
                        ->orWhere('author_name', $this->name);
                })
                ->orderByDesc('published_at');
        }

        if ($this->user) {
            return $this->user->posts()->published()->orderByDesc('published_at');
        }

        return Post::published()
            ->where('author_name', $this->name)
            ->orderByDesc('published_at');
    }
}
