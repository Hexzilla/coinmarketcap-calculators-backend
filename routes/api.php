<?php

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

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
  
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
    });
});

//Protecting Routes
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/profile', function(Request $request) {
        return auth()->user();
    });

    Route::post('/paypal/order/create', [App\Http\Controllers\PaypalController::class, 'create'])->name('paypal_create');
    Route::get('/paypal/order/capture', [App\Http\Controllers\PaypalController::class, 'capture'])->name('paypal_capture');

    Route::post('/stripe/secret', [App\Http\Controllers\StripeController::class, 'singleCharge'])->name('stripe_secret');
});
 
Route::get('exchange', [App\Http\Controllers\Api\ExchangeController::class, 'index']); 
Route::get('prices', [App\Http\Controllers\Api\ExchangeController::class, 'priceHistory']); 
