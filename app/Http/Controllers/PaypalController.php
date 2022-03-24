<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\PaypalGateway;
use App\Traits\ApiResponder;
use Validator;
use App\Plan;
use App\Credit;
use App\Promo;
use App\Subscription;
use Auth;
use Log;

class PaypalController extends Controller
{
    use ApiResponder;

    public function index()
    {
        
    }

    public function paypalSubscribe($plan_id,Request $request)
    {
        $plan = Plan::where('id',$plan_id)->first();
        if(!$plan){
            return redirect()->back()->with('error','Invalid plan!');
        }

        $user = Auth::user();

        $paypal = new PaypalGateway();
        $coupon_code = session('coupon_code');
        $coupon = Promo::where('voucher_code',$coupon_code)->first();

        $response = $paypal->createSubscription($plan,$user,$coupon);
        
        if(isset($response['type']) && $response['type'] == 'error'){
            Log::error($response['message']);
            
            return redirect()->back()->with('error','Some internal server error occurred, please try again later');
        }

        if(!isset($response['links'][0]['href']))
        {
            Log::error($response);
            
            return redirect()->back()->with('error','Some internal server error occurred, please try again later');
        }

        Subscription::create([
            'paypal_id' => $response['id'],
            'stripe_id' => '',
            'stripe_plan' => $plan->stripe_id,
            'stripe_status' => '',
            'paypal_status' => 'pending',
            'payment_method' => 'paypal',
            'user_id' => Auth::user()->id,
            'name' => $plan->plan_name,
            'quantity' => 1
        ]);

        session(['show_success' => true]);
        return redirect($response['links'][0]['href']);
    }
}
