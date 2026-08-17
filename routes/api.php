<?php

use App\Http\Controllers\Api\V1\AdminAttributeController;
use App\Http\Controllers\Api\V1\AdminCategoryController;
use App\Http\Controllers\Api\V1\AdminContactMessageController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminProductController;
use App\Http\Controllers\Api\V1\AdminReviewController;
use App\Http\Controllers\Api\V1\AdminSubmitOrderController;
use App\Http\Controllers\Api\V1\AdminTagController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BuildPackageController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SubmitOrderController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserAssetController;
use App\Http\Controllers\Api\V1\UserAvatarController;
use App\Http\Controllers\Api\V1\UserDashboardController;
use App\Http\Controllers\Api\V1\UserOrderController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Middleware\AuthenticateWithOnceBasic;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/products/{sku}/reviews', [ProductController::class, 'storeReview']);
        Route::post('/reviews/{review}/replies', [ProductController::class, 'storeReviewReply']);

        Route::post('/checkout/payment', [CheckoutController::class, 'payment']);
        Route::post('/orders/{order}/pay', [CheckoutController::class, 'repay']);
    });

    // Token-gated: IPG return often arrives without the Sanctum session cookie
    // (cross-site POST → SameSite=Lax). Auth is optional; ownership is checked when present.
    Route::get('/checkout/verify', [CheckoutController::class, 'verify']);

    Route::middleware(['auth:sanctum', 'verified'])->prefix('user')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'show']);
        Route::get('/profile', [UserProfileController::class, 'show']);
        Route::put('/profile', [UserProfileController::class, 'update']);
        Route::get('/orders', [UserOrderController::class, 'index']);
        Route::get('/orders/{order}', [UserOrderController::class, 'show']);
        Route::get('/avatars', [UserAvatarController::class, 'index']);
        Route::post('/avatars', [UserAvatarController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'verified'])->prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::put('/{ticket}', [TicketController::class, 'update']);
        Route::delete('/{ticket}', [TicketController::class, 'destroy']);
        Route::post('/{ticket}/responses', [TicketController::class, 'storeResponse']);
    });

    Route::middleware([StartSession::class])->group(function () {
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/{product}', [CartController::class, 'store']);
        Route::put('/cart/{product}', [CartController::class, 'update']);
        Route::delete('/cart/{product}', [CartController::class, 'destroy']);

        Route::get('/checkout', [CheckoutController::class, 'show']);
        Route::post('/checkout/account', [CheckoutController::class, 'account']);
    });

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/store-filters', [ProductController::class, 'storeFilters']);
    Route::get('/products/{sku}', [ProductController::class, 'show'])->name('api.v1.products.show');
    Route::get('/products/{sku}/reviews', [ProductController::class, 'reviews']);

    Route::get('/categories/popular', [CategoryController::class, 'popular']);
    Route::get('/categories/top-level', [CategoryController::class, 'topLevel']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->where('slug', '.*');

    Route::get('/tags/{slug}/products', [TagController::class, 'products']);

    Route::post('/contact-us', [ContactController::class, 'store']);
    Route::post('/submit-order', [SubmitOrderController::class, 'store']);

    Route::get('/build-package', [BuildPackageController::class, 'getBuildPackage']);

    Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'show']);

        Route::get('/products/form-data', [AdminProductController::class, 'formData']);
        Route::post('/products/import', [AdminProductController::class, 'import']);
        Route::post('/products/upload', [AdminProductController::class, 'upload']);
        Route::post('/products/temp-uploads/discard', [AdminProductController::class, 'discardTempUpload']);
        Route::delete('/products/{product}/images/{image}', [AdminProductController::class, 'destroyImage']);
        Route::delete('/products/{product}/files/{file}', [AdminProductController::class, 'destroyFile']);
        Route::apiResource('products', AdminProductController::class);

        Route::get('/categories/form-data', [AdminCategoryController::class, 'formData']);
        Route::apiResource('categories', AdminCategoryController::class);

        Route::get('/tags', [AdminTagController::class, 'index']);
        Route::post('/tags', [AdminTagController::class, 'store']);
        Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy']);

        Route::get('/attributes', [AdminAttributeController::class, 'index']);
        Route::post('/attributes', [AdminAttributeController::class, 'store']);
        Route::delete('/attributes/{attribute}', [AdminAttributeController::class, 'destroy']);

        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve']);
        Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy']);
        Route::get('/reviews/{review}/replies', [AdminReviewController::class, 'replies']);
        Route::post('/reviews/{review}/replies', [AdminReviewController::class, 'storeReply']);
        Route::post('/review-replies/{reply}/approve', [AdminReviewController::class, 'approveReply']);
        Route::delete('/review-replies/{reply}', [AdminReviewController::class, 'destroyReply']);

        Route::get('/users', [AdminUserController::class, 'index']);

        Route::get('/orders', [AdminSubmitOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminSubmitOrderController::class, 'show']);

        Route::get('/contact-messages', [AdminContactMessageController::class, 'index']);
    });

    Route::middleware([AuthenticateWithOnceBasic::class])->prefix('user/assets')->group(function () {
        Route::get('/categories', [UserAssetController::class, 'getCategories']);
        Route::get('/categories/{category}', [UserAssetController::class, 'getCategoryProducts']);
        Route::get('/products/{product}', [UserAssetController::class, 'getProduct']);
        Route::get('/search', [UserAssetController::class, 'search']);

        Route::get('/avatars', [UserAssetController::class, 'getAvatars']);
        Route::get('/avatars/{avatar}', [UserAssetController::class, 'getAvatar']);
    });
});
