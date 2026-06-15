<?php

namespace App\Models;

use App\Support\PublicImage;
use App\Support\AuthorProfile;
use App\Support\HtmlCleaner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'author_name',
        'published_at',
        'is_published',
        'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::saving(function (Post $post) {
            if ($post->user_id) {
                $user = $post->relationLoaded('user') ? $post->user : User::query()->find($post->user_id);

                if ($user) {
                    if (! $post->isDirty('author_name') || blank($post->author_name)) {
                        $post->author_name = $user->name;
                    }

                    $user->ensureAuthorSlug();
                }
            } elseif (blank($post->author_name)) {
                $post->author_name = config('site.default_author.name');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    public function hasStoredFeaturedImage(): bool
    {
        return PublicImage::isStored($this->featured_image);
    }

    public function featuredImageUrl(): ?string
    {
        return PublicImage::url($this->featured_image);
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function cardTitle(int $maxLength = 90): string
    {
        return static::normalizeTitle($this->title, $maxLength);
    }

    public static function normalizeTitle(string $title, int $maxLength = 90): string
    {
        $title = HtmlCleaner::decodeEntities($title);
        $title = preg_replace('/\s+/u', ' ', trim(str_replace(["\r\n", "\r", "\n"], ' ', $title)));

        return Str::limit($title, $maxLength, '…');
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => HtmlCleaner::decodeEntities($value ?? ''),
            set: fn (?string $value) => HtmlCleaner::decodeEntities($value ?? ''),
        );
    }

    public function seoDescription(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        return Str::limit(strip_tags($this->excerpt ?: $this->content), 160);
    }

    public function readingTime(): int
    {
        $words = str_word_count(strip_tags($this->content));

        return max(1, (int) ceil($words / 200));
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function authorProfile(): AuthorProfile
    {
        return AuthorProfile::forPost($this);
    }
}
