<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\VerificationController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/key/generate', [AuthController::class, 'generateUserKey']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/email/verify', [VerificationController::class, 'verify'])->middleware('throttle:3,1');
Route::post('/email/resend', [VerificationController::class, 'resendVerifyEmail'])->middleware('throttle:3,1');

Route::post('/password/forgot', [ResetPasswordController::class, 'sendResetLink'])->middleware('throttle:3,1');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->middleware('throttle:3,1');

Route::put('/profile/update', [AuthController::class, 'update_profile']);
Route::put('/password/update', [AuthController::class, 'change_password']);

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/show/{product}', [ProductController::class, 'show']);
    Route::get('/category/{category}', [ProductController::class, 'get_products_by_category']);
    Route::get('/subcategory/{subCategory}', [ProductController::class, 'get_products_by_subcategory']);
    Route::get('/deals/{category?}', [ProductController::class, 'get_discounted_deals']);
    Route::get('/top/{category?}', [ProductController::class, 'get_top_selling']);
    Route::get('/new/{category?}', [ProductController::class, 'get_new_arrivals']);
    Route::get('/filter/{category?}', [ProductController::class, 'filter_products']);
    Route::get('/sort', [ProductController::class, 'sort_products']);
    Route::get('/search/{search}', [ProductController::class, 'search_products']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
});

Route::prefix('subcategories')->group(function () {
    Route::get('/', [SubCategoryController::class, 'index']);
    Route::get('/{subCategory}', [SubCategoryController::class, 'show']);
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'getCart']);
    Route::post('/add/{product}', [CartController::class, 'addToCart']);
    Route::post('/clear', [CartController::class, 'clearCart']);
    Route::delete('/remove/{product}', [CartController::class, 'removeFromCart']);
});

Route::prefix('wishlist')->group(function () {
    Route::get('/', [WishlistController::class, 'getWishlist']);
    Route::post('/add/{product}', [WishlistController::class, 'addToWishlist']);
    Route::delete('/remove/{product}', [WishlistController::class, 'removeFromWishlist']);
});

Route::prefix('payment')->group(function () {
    Route::post('/initiate/{type}', [PaymentController::class, 'initiate']);
    Route::post('/callback', [PaymentController::class, 'paymentCallback']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::get('/tracking/get', [OrderController::class, 'tracking']);
});
