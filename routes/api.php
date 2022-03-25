<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeController;

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

//API route for register new user
Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register']);

//API route for login user
Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);

//Protecting Routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/profile', function(Request $request) {
        return auth()->user();
    });

    // API route for logout user
    Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);

    Route::get('/subscription/create', ['as'=>'home', 'uses' => 'SubscriptionController@index'])->name('subscription.create');
    Route::post('/order-post', ['as' => 'order-post', 'uses' => 'SubscriptionController@orderPost']);

    Route::post('/payment', 'PaymentController@payment')->name('payment');
    Route::get('/payment/status', 'PaymentController@paymentStatus')->name('payment_status');

    Route::get('/stripe/secret', 'StripeController@clientSecret')->name('stripe_secret');
});
