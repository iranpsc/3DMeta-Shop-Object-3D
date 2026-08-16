<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FileUploadController;
use App\Models\File;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Backend web endpoints kept after Livewire removal. Page UI lives in Next.js.
|
*/

Route::middleware('guest')->prefix('auth')->as('auth.')->group(function () {
    Route::get('/register', RegisterController::class)->name('register');
    Route::get('/redirect', [LoginController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [LoginController::class, 'callback'])->name('callback');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/download/{file}', function (Request $request, File $file) {
    return response()->download(storage_path("app/{$file->path}"), $file->name);
})->middleware('signed')->name('files.download');

Route::middleware(['auth'])->group(function () {
    Route::post('/upload', [FileUploadController::class, 'upload'])->name('files.upload');
});

Route::post('/callback', function (Request $request) {
    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
    $query = http_build_query($request->all());

    return redirect()->away("{$frontendUrl}/verify?{$query}");
})->name('callback')->withoutMiddleware([
    // Bank POSTs are cross-site, so SameSite=Lax omits the session cookie.
    // Starting a new session here would Set-Cookie and wipe the user's auth session
    // before the browser follows the redirect to the Next.js /verify page.
    StartSession::class,
    ShareErrorsFromSession::class,
    // Even when /callback is CSRF-excepted, PreventRequestForgery still calls
    // session()->token() afterward to set the XSRF-TOKEN cookie on the response.
    PreventRequestForgery::class,
]);
