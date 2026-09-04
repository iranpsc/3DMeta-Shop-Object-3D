<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class AdminReviewService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Review::with(['product:id,name,sku', 'user:id,name', 'replies'])
            ->latest()
            ->paginate($perPage);
    }

    public function findWithReplies(Review $review): Review
    {
        return $review->load('replies.user');
    }

    public function approve(Review $review, User $admin): Review
    {
        $review->approve($admin->name);

        return $review->fresh(['product:id,name,sku', 'user:id,name', 'replies']);
    }

    public function delete(Review $review): void
    {
        Gate::authorize('delete', $review);

        $review->delete();
    }

    /**
     * @param  array{comment: string}  $data
     */
    public function storeReply(User $admin, Review $review, array $data): ReviewReply
    {
        return $review->replies()->create([
            'user_id' => $admin->id,
            'comment' => $data['comment'],
        ]);
    }

    public function approveReply(ReviewReply $reply, User $admin): ReviewReply
    {
        $reply->approve($admin->name);

        return $reply->fresh('user');
    }

    public function deleteReply(ReviewReply $reply): void
    {
        Gate::authorize('delete', $reply);

        $reply->delete();
    }
}
