<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'avatar',
        'phone',
        'code',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => 'string',
            'phone' => 'string',
            'code' => 'string',
            'access_token' => 'string',
            'expires_in' => 'timestamp',
            'refresh_token' => 'string',
            'token_type' => 'string',
            'avatar' => 'string',
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'role' => 'user',
    ];

    /**
     * Get the user's role.
     *
     * @return string
     */
    public function role()
    {
        return $this->role;
    }

    /**
     * Determine if the user has the given role
     *
     * @return bool
     */
    public function hasRole(string $role)
    {
        return $this->role === $role;
    }

    /**
     * Get the orders for the user.
     *
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the transactions for the user.
     *
     * @return HasManyThrough
     */
    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Order::class);
    }

    /**
     * Get the products for the user.
     *
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /**
     * Check if user has purchased the given product.
     *
     * @return bool
     */
    public function hasPurchased(Product $product)
    {
        return $this->products()->where('product_id', $product->id)->exists();
    }

    /**
     * Get user's tickets
     *
     * @return HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Check if user has already added review for the given product.
     *
     * @return bool
     */
    public function hasReviewed(Product $product)
    {
        return Review::where('product_id', $product->id)->where('user_id', $this->id)->exists();
    }
}
