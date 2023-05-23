<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
 


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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//public routes
Route::controller(UserController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
});
Route::controller(ProductController::class)->group(function(){
    Route::get('cart', 'cart');
    Route::get('add-to-cart/{id}', 'addToCart');
    Route::get('remove-cart/{id}', 'removeFromCartByID');// from url
    Route::get('remove', 'removeFromCart');
    Route::get('empty-cart', 'removeAllCart');
    Route::patch('update-cart-quantity',  'updateCartQuantity');

});
Route::controller(MarketController::class)->group(function(){
    Route::get('local-markets', 'getAllLocalmarket');
    Route::get('supermarkets', 'getAllSupermarket');
    Route::post('add-market-wish-list',  'addMarketWishList');
    Route::get('market-type', 'getMarketType');
});
Route::controller(StoreController::class)->group(function(){
    Route::get('stores-in-martket', 'getAllStoresInMarket');
    Route::get('products-in-store', 'getAllProductsInAStore');
    Route::get('products-in-local-market', 'getAllProductInLocalMarket');

});

//private routes
Route::middleware('auth:sanctum')->group( function () {
    Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout.api');
    Route::get('checkout', [UserController::class, 'checkout']);
    Route::get('confirm_order', [OrderController::class, 'confirmOrder']);
    //ADMIN PAGE
    Route::post('add-store', [AdminController::class, 'addStoreToMarket']);

});

