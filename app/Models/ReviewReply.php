<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
        'comment',
        'approved',
        'approved_at',
        'approved_by',
    ];

    /**
     * Get the review that owns the reply.
     *
     * @return BelongsTo
     */
    public function review()
    {
        return $this->belongsTo(Review::class)->select('id', 'comment');
    }

    /**
     * Get the user that owns the reply.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'avatar');
    }

    /**
     * Scope a query to only include approved replies.
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
        $this->update([
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $approved_by,
        ]);
    }

    /**
     * Set the approved_at and approved_by attributes to null.
     *
     * @return void
     */
    public function disapprove()
    {
        $this->update([
            'approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Check if the reply is approved.
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->approved;
    }
}
