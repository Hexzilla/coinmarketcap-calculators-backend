<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::get('{reactRoutes}', function () {
//    return redirect('/'); //return view('welcome');
//})->where('reactRoutes', '^((?!api).)*$');


Route::get("/users/{id}/plans/new", 'UserController@newPlan')->name("users.plans.new");
Route::post("/users/{id}/plans/add", 'UserController@addPlan')->name("users.plans.add");
Route::get("/users/{id}/delete", 'UserController@destroy')->name("users.delete");

Route::resource('promo', 'PromoController');
Route::resource('plans', 'PlanController');
Route::resource('users', 'UserController');

Route::get("/users/{id}/credits/new", 'UserController@newCredits')->name("users.credits.new");
Route::post("/users/{id}/credits/add", 'UserController@addCredits')->name("users.credits.add");

Route::post('promo-delete', [App\Http\Controllers\PromoController::class, 'destroy'])->name('promo.delete');
Route::post('plans-delete', [App\Http\Controllers\PlanController::class, 'destroy'])->name('plans.delete');
Route::post('users-delete', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.delete');
    
Route::get("/payments", [App\Http\Controllers\SubscriptionController::class, 'payments'])->name("subscription.payments");
Route::post("/refund", [App\Http\Controllers\SubscriptionController::class, 'refund'])->name("subscription.refund");

Auth::routes();

Route::middleware(['auth'])->group(function() {
    
    Route::post("/plans-subscribe", [App\Http\Controllers\SubscriptionController::class, 'stripeSubscribe'])->name("plans.subscribe");

    Route::get("/subscription-success", [App\Http\Controllers\SubscriptionController::class, 'subscriptionSuccess'])->name("subscription.success");
    Route::get("/subscription-failed", [App\Http\Controllers\SubscriptionController::class, 'failed'])->name("subscription.failed");

    Route::get('/billing', [App\Http\Controllers\SubscriptionController::class, 'index'])->name("manage.billing");
    Route::get('/billing-cancel/{id}', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name("plan.cancel");
    Route::post('/apply-coupon', [App\Http\Controllers\SubscriptionController::class, 'applyCoupon'])->name("apply.coupon");
    Route::post('/remove-coupon', [App\Http\Controllers\SubscriptionController::class, 'removeCoupon'])->name("remove.coupon");
    Route::get('/paypal-subscribe/{plan_id}', [App\Http\Controllers\SubscriptionController::class, 'paypalSubscribe'])->name('paypal.subscribe');
});

Route::post("paypal/webhook",[App\Http\Controllers\WebhookController::class, 'index']);

