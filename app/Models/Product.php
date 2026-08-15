<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Product extends Model implements Sitemapable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'short_description',
        'long_description',
        'stock_status',
        'quantity',
        'delivery_time',
        'customer_can_add_review',
        'price',
        'sale_price',
        'published',
        'meta_description',
        'meta_keywords',
        'created_by',
    ];

    protected $attributes = [
        'sku' => '',
        'name' => '',
        'slug' => '',
        'short_description' => '',
        'long_description' => '',
        'stock_status' => false,
        'quantity' => 0,
        'customer_can_add_review' => false,
        'price' => 0.0,
        'sale_price' => 0.0,
        'published' => false,
        'meta_description' => '',
        'meta_keywords' => '',
        'created_by' => 'admin',
    ];

    protected $appends = [
        'url',
        'discount',
        'final_price',
        'is_free',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'stock_status' => 'boolean',
            'customer_can_add_review' => 'boolean',
        ];
    }

    public function setDelevileryTimeAttribute($value)
    {
        $this->attributes['delivery_time'] = $value ? $value : now();
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getIsFreeAttribute()
    {
        return $this->price == 0;
    }

    public function getUrlAttribute()
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return "{$frontendUrl}/products/{$this->sku}";
    }

    public function toSitemapTag(): Url|string|array
    {
        $url = Url::create($this->url)
            ->setLastModificationDate($this->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.8);

        return $url;
    }

    /**
     * Get products created by admin.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeCreatedByAdmin($query)
    {
        return $query->where('created_by', 'admin');
    }

    /**
     * Get the category that owns the product.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the images for the product.
     *
     * @return MorphMany
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the latest image for the product.
     *
     * @return MorphOne
     */
    public function latestImage()
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    /**
     * Get the oldest image for the product.
     *
     * @return MorphOne
     */
    public function oldestImage()
    {
        return $this->morphOne(Image::class, 'imageable')->oldestOfMany();
    }

    /**
     * Get the files for the product.
     *
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the tags for the product.
     *
     * @return BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the attributes for the product.
     *
     * @return BelongsToMany
     */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class)->withPivot('value');
    }

    /**
     * Get user's that own the product.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('download_count', 'downloaded_at', 'quantity');
    }

    /**
     * Get published products.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Get product discount
     *
     * @return float
     */
    public function getDiscountAttribute()
    {
        return $this->sale_price ? round((1 - $this->sale_price / $this->price) * 100) : 0;
    }

    /**
     * Get product final price
     *
     * @return float
     */
    public function getFinalPriceAttribute()
    {
        return $this->sale_price > 0 ? $this->sale_price : $this->price;
    }

    /**
     * Get product sales
     *
     * @return HasManyThrough
     */
    public function sales()
    {
        return $this->hasManyThrough(
            Order::class,
            OrderItem::class,
            'product_id',
            'id',
            'id',
            'order_id'
        )->where('status', 0);
    }

    /**
     * Get product reviews
     *
     * @return HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class)->with('user')->approved();
    }

    /**
     * Get product orders
     *
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get product average rating
     *
     * @return float
     */
    public function hasOrders()
    {
        return $this->orders()->exists();
    }

    /**
     * Get product likes
     *
     * @return MorphMany
     */
    public function likes()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('like');
    }

    /**
     * Get product shares
     *
     * @return MorphMany
     */
    public function shares()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('share');
    }

    /**
     * Get product downloads
     *
     * @return MorphMany
     */
    public function downloads()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('download');
    }

    /**
     * Get product views
     *
     * @return MorphMany
     */
    public function views()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('view');
    }

    /**
     * Get product bookmarks
     *
     * @return MorphMany
     */
    public function bookmarks()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('bookmark');
    }
}
