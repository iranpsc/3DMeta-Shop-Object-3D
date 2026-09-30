<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    /**
     * @param  array{comment: string, rating: int}  $data
     */
    public function update(Review $review, array $data): Review
    {
        $review->update([
            'comment' => $data['comment'],
            'rating' => $data['rating'],
            'approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return $review->fresh(['product:id,name,sku', 'user:id,name', 'replies']);
    }

    public function delete(Review $review): void
    {
        if ($review->approved || $review->replies()->where('approved', true)->exists()) {
            abort(403, 'دیدگاه تایید شده قابل حذف نیست.');
        }

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

    /**
     * @param  array{comment: string}  $data
     */
    public function updateReply(ReviewReply $reply, array $data): ReviewReply
    {
        $reply->update([
            'comment' => $data['comment'],
            'approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return $reply->fresh('user');
    }

    public function deleteReply(ReviewReply $reply): void
    {
        if ($reply->approved) {
            abort(403, 'پاسخ تایید شده قابل حذف نیست.');
        }

        $reply->delete();
    }
}
