<?php

namespace App\Policies;

use App\Models\ReviewReply;
use App\Models\User;

class ReviewReplyPolicy
{
    /**
     * Determine whether the user can delete the review reply.
     *
     * Unverified replies may be deleted by an admin; approved replies cannot.
     */
    public function delete(User $user, ReviewReply $reply): bool
    {
        return $user->hasRole('admin') && ! $reply->approved;
    }
}
