<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'comment',
        'rating',
        'approved',
        'approved_at',
        'approved_by',
    ];

    protected function casts()
    {
        return [
            'approved' => 'boolean',
            'approved_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    /**
     * Get the product that owns the review.
     *
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class)->select('id', 'name');
    }

    /**
     * Get the user that owns the review.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'avatar');
    }

    /**
     * Scope a query to only include approved reviews.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeApproved($query)
    {
        $query->where('approved', 1);
    }

    /**
     * Set the approved_at and approved_by attributes.
     *
     * @param  string  $approved_by
     * @return void
     */
    public function approve($approved_by)
    {
        $this->approved = true;
        $this->approved_at = now();
        $this->approved_by = $approved_by;
        $this->save();
    }

    /**
     * Get the likes for the review.
     *
     * @return MorphMany
     */
    public function likes()
    {
        return $this->morphMany(Interaction::class, 'interactable')->type('like');
    }

    /**
     * Get the replies for the review.
     *
     * @return HasMany
     */
    public function replies()
    {
        return $this->hasMany(ReviewReply::class);
    }
}
