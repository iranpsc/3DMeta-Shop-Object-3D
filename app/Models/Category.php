<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Category extends Model implements Sitemapable
{
    use HasFactory, HasRelationships;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
    ];

    protected $casts = [
        'parent_id' => 'integer'
    ];

    /**
     * Get the URL attribute for the category.
     *
     * Walks the parent chain iteratively and breaks on cycles/self-parents
     * so recursive accessors cannot exhaust memory.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        $segments = [];
        $category = $this;
        $visited = [];

        while ($category) {
            if (isset($visited[$category->id])) {
                break;
            }

            $visited[$category->id] = true;
            array_unshift($segments, $category->slug);

            $category->loadMissing('parent');
            $parent = $category->relationLoaded('parent') ? $category->parent : null;

            if (! $parent || $parent->id === $category->id) {
                break;
            }

            $category = $parent;
        }

        return implode('/', $segments);
    }

    /**
     * Get the breadcrumb attribute for the category.
     *
     * @return string
     */
    public function getBreadcrumbAttribute()
    {
        $parts = [];
        $category = $this;
        $visited = [];

        while ($category) {
            if (isset($visited[$category->id])) {
                break;
            }

            $visited[$category->id] = true;

            array_unshift(
                $parts,
                '<a href="' . url('categories/' . $category->url) . '">' . e($category->name) . '</a>'
            );

            $category->loadMissing('parent');
            $parent = $category->relationLoaded('parent') ? $category->parent : null;

            if (! $parent || $parent->id === $category->id) {
                break;
            }

            $category = $parent;
        }

        return implode(' / ', $parts);
    }

    /**
     * Convert the category to a sitemap tag.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string|array
     */
    public function toSitemapTag(): Url|string|array
    {
        $url = Url::create(url('categories/' . $this->url))
            ->setLastModificationDate($this->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.8);

        return $url;
    }

    /**
     * Get the parent category that owns the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * Get the children categories for the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    /**
     * Get the products for the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the image for the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
