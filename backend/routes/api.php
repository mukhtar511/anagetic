<?php

use App\Http\Controllers\Api\AddonOfferController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\SmartRequestController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Seller\CouponController as SellerCouponController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\FeaturedController as SellerFeaturedController;
use App\Http\Controllers\Seller\ListingController as SellerListingController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\RentalController as SellerRentalController;
use App\Http\Controllers\Seller\SmartRequestController as SellerSmartRequestController;
use App\Http\Controllers\Seller\StoreController as SellerStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public — auth gateway + guest-browsable catalog. SPEC §3.
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('throttle:6,1')->group(function () {
    // Rate-limit OTP to curb abuse. SPEC §8.
    Route::post('otp/request', [AuthController::class, 'requestOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
});

Route::get('regions', [CatalogController::class, 'regions']);
Route::get('products', [CatalogController::class, 'products']);
Route::get('products/{product}', [CatalogController::class, 'product']);
Route::get('featured', [CatalogController::class, 'featured']);
Route::get('stores', [CatalogController::class, 'stores']);
Route::get('stores/{store}', [CatalogController::class, 'store']);
Route::get('policies', [PolicyController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Authenticated — buyer + seller. SPEC §3.
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Cart.
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::delete('cart/items/{cartItem}', [CartController::class, 'removeItem']);
    Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
    Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);

    // Checkout.
    Route::post('checkout', [CheckoutController::class, 'store']);

    // Orders (buyer).
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('orders/{order}/return', [OrderController::class, 'requestReturn']);
    Route::post('orders/{order}/rating', [OrderController::class, 'rate']);

    // Wallet.
    Route::get('wallet', [WalletController::class, 'show']);
    Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);

    // Deposits.
    Route::get('deposits', [DepositController::class, 'index']);
    Route::post('deposits/{deposit}/appeal', [DepositController::class, 'appeal']);

    // Favorites.
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    Route::delete('favorites/{product}', [FavoriteController::class, 'destroy']);

    // Notifications.
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);

    // Smart requests (buyer) + offers.
    Route::get('smart-requests', [SmartRequestController::class, 'index']);
    Route::post('smart-requests', [SmartRequestController::class, 'store'])->middleware('throttle:10,1');
    Route::post('smart-requests/{smartRequest}/close', [SmartRequestController::class, 'close']);
    Route::post('offers/{offer}/accept', [OfferController::class, 'accept']);

    // Chat.
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('conversations/{conversation}/messages', [ConversationController::class, 'postMessage']);
    Route::post('conversations/{conversation}/addon-offers', [ConversationController::class, 'addonOffer']);
    Route::post('addon-offers/{addonOffer}/accept', [AddonOfferController::class, 'accept']);
    Route::post('addon-offers/{addonOffer}/reject', [AddonOfferController::class, 'reject']);

    /*
    |----------------------------------------------------------------------
    | Seller — must own a store (enforced in-controller). SPEC §3.4–§3.9.
    |----------------------------------------------------------------------
    */
    Route::post('products', [SellerProductController::class, 'store']);
    Route::put('products/{product}', [SellerProductController::class, 'update']);
    Route::delete('products/{product}', [SellerProductController::class, 'destroy']);

    Route::post('listings/ai-fill', [SellerListingController::class, 'aiFill']);
    Route::post('products/{product}/featured', [SellerFeaturedController::class, 'store']);

    Route::get('seller/dashboard', [SellerDashboardController::class, 'index']);
    Route::post('seller/orders/{order}/confirm-delivery', [SellerOrderController::class, 'confirmDelivery']);
    Route::post('seller/rentals/{booking}/sound', [SellerRentalController::class, 'sound']);
    Route::post('seller/rentals/{booking}/dispute', [SellerRentalController::class, 'dispute']);

    Route::get('seller/coupons', [SellerCouponController::class, 'index']);
    Route::post('seller/coupons', [SellerCouponController::class, 'store']);
    Route::patch('seller/coupons/{coupon}', [SellerCouponController::class, 'update']);
    Route::delete('seller/coupons/{coupon}', [SellerCouponController::class, 'destroy']);

    Route::put('store', [SellerStoreController::class, 'update']);

    Route::get('seller/smart-requests', [SellerSmartRequestController::class, 'index']);
    Route::post('smart-requests/{smartRequest}/offers', [SellerSmartRequestController::class, 'submitOffer']);
});
