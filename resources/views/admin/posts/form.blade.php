@extends('layouts.admin')

@section('title', $post->exists ? 'Edit Post' : 'New Post')

@section('content')
<h1 style="margin-bottom:1.5rem;">{{ $post->exists ? 'Edit' : 'Create' }} Blog Post</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
    @csrf
    @if($post->exists) @method('PUT') @endif

    <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}" required>
    </div>
    <div class="form-group">
        <label>Excerpt (summary for listings &amp; SEO)</label>
        <textarea name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
    </div>
    <div class="form-group">
        <label>Content * (HTML allowed: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;a&gt;)</label>
        <textarea name="content" rows="18" required style="max-width:100%;font-family:monospace;">{{ old('content', $post->content) }}</textarea>
    </div>
    <h3 style="margin:1.5rem 0 .75rem;">SEO</h3>
    <div class="form-group">
        <label>Meta title (max 70 chars)</label>
        <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="70">
    </div>
    <div class="form-group">
        <label>Meta description (max 320 chars)</label>
        <textarea name="meta_description" rows="2" maxlength="320">{{ old('meta_description', $post->meta_description) }}</textarea>
    </div>
    <div class="form-group">
        <label>Featured image</label>
        @if($post->featuredImageUrl())
            <div class="admin-image-preview">
                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="admin-preview-img admin-preview-img--blog">
            </div>
        @endif
        <input type="file" name="featured_image_file" accept="image/*" style="margin-top:.5rem;">
        <p class="form-hint">Upload JPG, PNG or WebP (max 5MB). Or paste an image URL below.</p>
        <input type="url" name="featured_image" value="{{ old('featured_image', $post->hasStoredFeaturedImage() ? '' : $post->featured_image) }}" placeholder="https://example.com/image.jpg" style="margin-top:.5rem;">
    </div>
    <div class="form-group">
        <label>Author name</label>
        <input type="text" name="author_name" value="{{ old('author_name', $post->author_name) }}" placeholder="Leave blank to use your account name automatically">
        <p class="form-hint">If empty, the post will use your logged-in name and link to your public author profile.</p>
    </div>
    <div class="form-group">
        <label>Publish date</label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="form-check">
        <input type="checkbox" name="is_published" value="1" id="published" @checked(old('is_published', $post->is_published))>
        <label for="published">Published</label>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save Post</button>
    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline">Cancel</a>
</form>
@endsection
