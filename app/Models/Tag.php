<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Tag extends Model implements Sitemapable
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * Get the products for the tag.
     *
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = trim(str_replace(' ', '-', $value));
    }

    public function getUrlAttribute()
    {
        return url('/tags/'.trim($this->slug));
    }

    public function toSitemapTag(): Url|string|array
    {
        return Url::create($this->url)
            ->setLastModificationDate($this->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.8);
    }
}
