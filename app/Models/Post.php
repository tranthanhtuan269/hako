<?php

namespace App\Models;

use App\Support\PublicImage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
}
