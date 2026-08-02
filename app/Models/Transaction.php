<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'token',
        'amount',
        'currency',
        'status',
        'card_hash',
        'card_pan',
        'fee_type',
        'fee',
        'reference_id',
    ];

    /**
     * Casts properties
     *
     * @return array
     */
    protected function casts() {
        return [
            'status' => 'int'
        ];
    }

    /**
     * Attributes with default values
     *
     * @return array
     */
    protected $attributes = [
        'status' => -1
    ];

    /**
     * Get the order for the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
