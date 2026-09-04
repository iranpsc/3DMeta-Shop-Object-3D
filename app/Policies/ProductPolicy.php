<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Product $product)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('admin') && ! $product->users()->exists();
    }

    public function import(User $user)
    {
        return $user->hasRole('admin');
    }

    public function download(User $user, Product $product): bool
    {
        return $user->orders()->whereHas('products', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->exists() || $product->is_free;
    }

    public function addReview(User $user, Product $product): Response
    {
        if ($product->is_free && $product->customer_can_add_review) {
            return Response::allow();
        }

        return $user->hasPurchased($product)
            && ! $user->hasReviewed($product)
            && $product->customer_can_add_review
            ? Response::allow()
            : Response::deny('شما قادر به افزودن بازخورد برای این محصول نیستید.');
    }

    public function approveReview(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteReview(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
