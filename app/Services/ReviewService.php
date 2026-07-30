<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ReviewService
{
    /**
     * Approved reviews payload for a product (parity with Reviews Livewire).
     *
     * @return array{reviews: \Illuminate\Database\Eloquent\Collection, rating_breakdown: array<string, int>, users_count: int}
     */
    public function forProduct(Product $product): array
    {
        $product->load(['reviews.user', 'reviews.replies' => function ($query) {
            $query->approved()->orderByDesc('created_at')->with('user');
        }])->loadCount([
            'reviews as five_star_reviews_count' => fn ($query) => $query->where('rating', 5),
            'reviews as four_star_reviews_count' => fn ($query) => $query->where('rating', 4),
            'reviews as three_star_reviews_count' => fn ($query) => $query->where('rating', 3),
            'reviews as two_star_reviews_count' => fn ($query) => $query->where('rating', 2),
            'reviews as one_star_reviews_count' => fn ($query) => $query->where('rating', 1),
        ]);

        return [
            'reviews' => $product->reviews,
            'rating_breakdown' => [
                'five' => (int) $product->five_star_reviews_count,
                'four' => (int) $product->four_star_reviews_count,
                'three' => (int) $product->three_star_reviews_count,
                'two' => (int) $product->two_star_reviews_count,
                'one' => (int) $product->one_star_reviews_count,
            ],
            'users_count' => User::count(),
        ];
    }

    /**
     * Create a review (parity with Reviews::saveReview).
     *
     * @param  array{comment: string, rating: int}  $data
     */
    public function store(User $user, Product $product, array $data): Review
    {
        Gate::authorize('addReview', $product);

        return Review::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => $data['comment'],
            'rating' => $data['rating'],
        ]);
    }

    /**
     * Create a review reply (parity with Reviews::saveReviewReply).
     *
     * @param  array{comment: string}  $data
     */
    public function storeReply(User $user, Review $review, array $data): ReviewReply
    {
        return $review->replies()->create([
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);
    }
}
