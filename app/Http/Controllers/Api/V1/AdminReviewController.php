<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdminReviewReplyRequest;
use App\Http\Resources\AdminReviewReplyResource;
use App\Http\Resources\AdminReviewResource;
use App\Http\Resources\ApiResource;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Services\AdminReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function __construct(
        private AdminReviewService $reviews,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->reviews->paginate();

        return ApiResource::success([
            'data' => AdminReviewResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function approve(Request $request, Review $review): JsonResponse
    {
        $review = $this->reviews->approve($review, $request->user());

        return ApiResource::success(
            (new AdminReviewResource($review))->resolve(),
            'دیدگاه با موفقیت تایید شد.'
        );
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->reviews->delete($review);

        return ApiResource::success(null, 'دیدگاه با موفقیت حذف شد.');
    }

    public function replies(Review $review): JsonResponse
    {
        $review = $this->reviews->findWithReplies($review);

        return ApiResource::success([
            'review' => (new AdminReviewResource($review))->resolve(),
            'replies' => AdminReviewReplyResource::collection($review->replies)->resolve(),
        ]);
    }

    public function storeReply(StoreAdminReviewReplyRequest $request, Review $review): JsonResponse
    {
        $reply = $this->reviews->storeReply(
            $request->user(),
            $review,
            $request->validated()
        );

        return ApiResource::success(
            (new AdminReviewReplyResource($reply->load('user')))->resolve()
        );
    }

    public function approveReply(Request $request, ReviewReply $reply): JsonResponse
    {
        $reply = $this->reviews->approveReply($reply, $request->user());

        return ApiResource::success(
            (new AdminReviewReplyResource($reply))->resolve(),
            'پاسخ با موفقیت تایید شد.'
        );
    }

    public function destroyReply(ReviewReply $reply): JsonResponse
    {
        $this->reviews->deleteReply($reply);

        return ApiResource::success(null, 'پاسخ با موفقیت حذف شد.');
    }
}
