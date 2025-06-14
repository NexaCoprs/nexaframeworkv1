<?php

namespace Workspace\Database\Entities;

use Nexa\Database\Model;
use Nexa\Attributes\Cache;
use Nexa\Attributes\Validate;
use Nexa\Attributes\Secure;
use Nexa\Attributes\Route;
use Nexa\Attributes\API;
use Nexa\Attributes\Relation;
use Nexa\Attributes\AutoValidate;

/**
 * Post entity for blog posts and content
 */
#[Cache('Post', 1800), Validate, Secure, AutoValidate]
#[Route(prefix: '/posts'), API(version: 'v1')]
class Post extends Model
{
    /**
     * Fillable attributes
     */
    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'status',
        'published_at',
        'user_id',
        'category_id',
        'featured_image',
        'meta_title',
        'meta_description',
        'slug'
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_featured' => 'boolean',
        'view_count' => 'integer'
    ];

    /**
     * Validation rules
     */
    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'status' => 'required|in:draft,published,archived',
        'user_id' => 'required|integer|exists:users,id',
        'slug' => 'required|string|unique:posts,slug'
    ];

    /**
     * Relationship with User (author)
     */
    #[Relation('belongsTo', User::class)]
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with Category
     */
    #[Relation('belongsTo', Category::class)]
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relationship with Comments
     */
    #[Relation('hasMany', Comment::class)]
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relationship with Tags (many-to-many)
     */
    #[Relation('belongsToMany', Tag::class)]
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    /**
     * Scope for published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for featured posts
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get post excerpt
     */
    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        return substr(strip_tags($this->content), 0, 150) . '...';
    }

    /**
     * Generate slug from title
     */
    public function generateSlug()
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->title)));
        return rtrim($slug, '-');
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Check if post is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at && 
               $this->published_at <= now();
    }

    /**
     * Get reading time estimate
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }
}