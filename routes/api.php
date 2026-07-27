<?php

use App\Http\Controllers\Api\V1\BuildPackageController;
use App\Http\Controllers\Api\V1\UserAssetController;
use App\Http\Middleware\AuthenticateWithOnceBasic;
use Illuminate\Http\Request;
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
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/build-package', [BuildPackageController::class, 'getBuildPackage']);

    Route::middleware([AuthenticateWithOnceBasic::class])->prefix('user/assets')->group(function () {
        Route::get('/categories', [UserAssetController::class, 'getCategories']);
        Route::get('/categories/{category}', [UserAssetController::class, 'getCategoryProducts']);
        Route::get('/products/{product}', [UserAssetController::class, 'getProduct']);
        Route::get('/search', [UserAssetController::class, 'search']);

        Route::get('/avatars', [UserAssetController::class, 'getAvatars']);
        Route::get('/avatars/{avatar}', [UserAssetController::class, 'getAvatar']);
    });
});
