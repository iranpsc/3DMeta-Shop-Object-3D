<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine whether the user can delete the review.
     *
     * Unverified comments may be deleted by an admin; approved comments cannot.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->hasRole('admin') && ! $review->approved;
    }
}
