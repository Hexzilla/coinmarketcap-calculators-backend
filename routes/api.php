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

    /*Route::get('/subscription/create', ['as'=>'home', 'uses' => 'SubscriptionController@index'])->name('subscription.create');
    Route::post('/order-post', ['as' => 'order-post', 'uses' => 'SubscriptionController@orderPost']);

    Route::post('/payment', 'PaymentController@payment')->name('payment');
    Route::get('/payment/status', 'PaymentController@paymentStatus')->name('payment_status');*/

    Route::post('/stripe/secret', [App\Http\Controllers\StripeController::class, 'clientSecret'])->name('stripe_secret');
});
 
Route::get('exchange', [App\Http\Controllers\Api\ExchangeController::class, 'index']); 
Route::get('prices', [App\Http\Controllers\Api\ExchangeController::class, 'priceHistory']); 
