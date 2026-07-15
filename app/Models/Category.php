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
     * @return string
     */
    public function getUrlAttribute()
    {
        $this->loadMissing('parent');

        return $this->parent ? $this->parent->url . '/' . $this->slug : $this->slug;
    }

    /**
     * Get the breadcrumb attribute for the category.
     *
     * @return string
     */
    /**
     * Get the breadcrumb attribute for the category.
     *
     * @return string
     */
    public function getBreadcrumbAttribute()
    {
        $this->loadMissing('parent');

        $url = '<a href="' . url('categories/' . $this->url) . '">' . e($this->name) . '</a>';

        return $this->parent ? $this->parent->breadcrumb . ' / ' . $url : $url;
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
