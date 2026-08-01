<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FileUploadController;
use App\Livewire\AboutUs;
use App\Livewire\Avatars;
use App\Livewire\ContactUsMessages;
use App\Livewire\AdminDashboard;
use App\Livewire\Cart;
use App\Livewire\Checkout\Checkout;
use App\Livewire\Checkout\Verify;
use App\Livewire\Home;
use App\Livewire\ProductCategory;
use App\Livewire\ProductTag;
use App\Livewire\Store;
use App\Livewire\ContactUs;
use App\Livewire\ProductCategories;
use App\Livewire\SubmitOrder;
use App\Livewire\StoreManagement\SubmitedOrders\Listing as SubmitedOrdersListing;
use App\Livewire\StoreManagement\SubmitedOrders\Show as SubmitedOrdersShow;
use App\Livewire\StoreManagement\Attributes;
use App\Livewire\StoreManagement\Categories\Categories;
use App\Livewire\StoreManagement\Categories\CreateCategory;
use App\Livewire\StoreManagement\Categories\EditCategory;
use App\Livewire\StoreManagement\Tags;
use App\Livewire\StoreManagement\Products\CreateProduct;
use App\Livewire\StoreManagement\Products\EditProduct;
use App\Livewire\StoreManagement\Products\Import;
use App\Livewire\StoreManagement\Products\Products;
use App\Livewire\ProductDetails;
use App\Livewire\StoreManagement\Products\ReviewReplies;
use App\Livewire\StoreManagement\Products\Reviews;
use App\Livewire\Support\CreateTicket;
use App\Livewire\Support\ShowTicket;
use App\Livewire\Support\Tickets;
use App\Livewire\Support\UpdateTicket;
use App\Livewire\User\Orders;
use App\Livewire\User\Profile;
use App\Livewire\User\Dashboard;
use App\Livewire\User\OrderDetails;
use App\Livewire\Users;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

Route::livewire('/', Home::class)->name('home');
Route::livewire('/about-us', AboutUs::class)->name('about-us');
Route::livewire('/contact-us', ContactUs::class)->name('contact-us');
Route::livewire('/products', Store::class)->name('products');
Route::livewire('/products/{product:sku}', ProductDetails::class)->name('products.show');
Route::livewire('/categories', ProductCategories::class)->name('categories');
Route::livewire('/categories/{category_link}', ProductCategory::class)->where('category_link', '.*')->name('categories.show');
Route::livewire('/tags/{tag:slug}', ProductTag::class)->name('tags.show');
Route::livewire('/cart', Cart::class)->name('cart');
Route::livewire('/checkout', Checkout::class)->name('checkout');
Route::livewire('/submit-order', SubmitOrder::class)->name('submit-order');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::livewire('/avatars', Avatars::class)->name('avatars');

    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::livewire('/dashboard', AdminDashboard::class)->name('admin.dashboard');

        Route::prefix('products')->name('products.')->group(function () {
            Route::livewire('/', Products::class)->name('index');
            Route::livewire('/create', CreateProduct::class)->name('create');
            Route::livewire('/{product}/edit', EditProduct::class)->name('edit');
            Route::livewire('/import', Import::class)->name('import');
        });

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::livewire('/', Categories::class)->name('index');
            Route::livewire('/create', CreateCategory::class)->name('create');
            Route::livewire('/{category}/edit', EditCategory::class)->name('edit');
        });

        Route::livewire('/tags', Tags::class)->name('tags');

        Route::livewire('/attributes', Attributes::class)->name('attributes');

        Route::livewire('/reviews', Reviews::class)->name('reviews');

        Route::livewire('/reviews/{review}/replies', ReviewReplies::class)->name('review-replies');

        Route::livewire('/users', Users::class)->name('users');

        Route::livewire('/submited-orders', SubmitedOrdersListing::class)->name('submited-orders-index');
        Route::livewire('/submited-orders/{order}', SubmitedOrdersShow::class)->name('submited-orders-show');
        Route::livewire('/contact-us-messages', ContactUsMessages::class)->name('contact-us-messages');
    });

    Route::livewire('/verify', Verify::class)->name('verify');

    Route::as('user.')->group(function () {
        Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
        Route::livewire('/orders', Orders::class)->name('orders');
        Route::livewire('/orders/{order}', OrderDetails::class)->name('orders.show');
        Route::livewire('/profile', Profile::class)->name('profile');
    });

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::livewire('/', Tickets::class)->name('index');
        Route::livewire('/create', CreateTicket::class)->name('create');
        Route::livewire('/{ticket}', ShowTicket::class)->name('show');
        Route::livewire('/{ticket}/edit', UpdateTicket::class)->name('edit');
    });
});

Route::middleware('guest')->prefix('auth')->group(function () {
    Route::get('/register', RegisterController::class)->name('register');
    Route::get('/redirect', [LoginController::class, 'redirect'])->name('login');
    Route::get('/callback', [LoginController::class, 'callback'])->name('auth.callback');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/download/{file}', function (Request $request, File $file) {
    Log::info(storage_path("app/{$file->path}"));
    return response()->download(storage_path("app/{$file->path}"), $file->name);
})->middleware('signed:relative')->name('files.download');

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
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    // Even when /callback is CSRF-excepted, PreventRequestForgery still calls
    // session()->token() afterward to set the XSRF-TOKEN cookie on the response.
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
]);
