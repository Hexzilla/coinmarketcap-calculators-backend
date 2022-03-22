<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\StripeGateway;
use App\Utils\PaypalGateway;
use Carbon\Carbon;
use App\Traits\ApiResponder;
use Validator;
use App\Plan;
use App\Credit;
use App\Promo;
use App\Subscription;
use App\SubscriptionRefunds;
use Auth;
use Log;
use Session;

class SubscriptionController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $data['plans'] = Plan::all();

        $user = Auth::user();

        // get active subscription of the user
        $active_subscription = $user->subscriptions()->first();
        $data['active_subscription'] = $active_subscription;

        $data['active_plan'] = null;
        if ($active_subscription) {
            foreach($data['plans'] as $key => $plan) {
                if ($active_subscription->stripe_plan == $plan->stripe_id) {
                    $data['active_plan'] = $plan;
                    break;
                }
            }
            $data['cancelled'] = $active_subscription->paypal_status == 'cancelled' || $active_subscription->stripe_status == 'cancelled';
            $data['cancelled_date'] = Carbon::parse($active_subscription->ends_at)->format('Y/m/d');
        }

        $coupon_code = session('coupon_code');
        $coupon = Promo::where('voucher_code',$coupon_code)->first();
        $data['coupon'] = $coupon;

        $data['intent'] = $user->createSetupIntent();
        return view('user-admin.subscription.plans',$data);
    }

    public function payments()
    {
        $payments = Subscription::orderBy('id','desc')->paginate(10);
        return view('admin.payment', compact('payments'));
    }

    public function refund(Request $request)
    {
        $this->validate($request, [
            'payment' => ['required']
        ]);

        $payment_id = $request->get('payment');

        $subscription = Subscription::find($payment_id);
        if(!$subscription){
            return redirect()->back()->with('error', 'Invalid request');
        }

        if($subscription['payment_method'] == 'stripe')
        {
            try{
                $gateway = new StripeGateway();

                // get subscription details from the stripe
                $stripeSubscription = $gateway->stripe->subscriptions->retrieve($subscription['stripe_id'],[]);
               
                $invoice = $gateway->stripe->invoices->retrieve($stripeSubscription['latest_invoice'],[]);
                
                $refund = $gateway->stripe->refunds->create([
                    'charge' => $invoice->charge,
                ]);

                
                SubscriptionRefunds::create([
                    'subscription_tbl_id' => $subscription['id'],
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'status' => $refund->status,
                ]);

                // after this cancel subscription
                $gateway->stripe->subscriptions->cancel($stripeSubscription->id,[]);

                $subscription->stripe_status = 'cancelled';
                $subscription->save();

            }catch(\Exception $e){
                return redirect()->back()->with('error', $e->getMessage());
            }
        }elseif($subscription['payment_method'] == 'paypal'){
            $gateway = new PaypalGateway();
            
            $paypalSubscription = $gateway->provider->showSubscriptionDetails($subscription['paypal_id']);
            prd($paypalSubscription);
            prd($response);
            prd($paypalSubscription);

            $subscription->paypal_status = 'cancelled';
            $subscription->save();
        }
        return redirect()->back()->with('success', 'Refund Success');
    }

    public function stripeSubscribe(Request $request)
    {
        $this->validate($request, [
            'payment_method' => ['required'],
            'payment_method_id' => ['required'],
        ]);

        $user = Auth::user();

        if (is_null($user->stripe_id)) {
            $stripeCustomer = $user->createAsStripeCustomer();
        }

        $plan = Plan::where('id',$request->get('plan_id'))->first();

        // check if user is already subscribed to the choosen plan
        $active_subscription = $user->subscriptions()->first();
        if($active_subscription){
            if($active_subscription->stripe_plan == $plan->stripe_id && $active_subscription->stripe_status == 'active'){
                $request->session()->flash('error', 'You are already subscribed to this plan');
                return response()->json([
                    'error' => true,
                    'redirect_url' => route('manage.billing')
                ]);
            }
        }

        // check if coupon code is applied or not
        $coupon_code = session('coupon_code');
        $coupon = Promo::where('voucher_code',$coupon_code)->first();

        try{
            if($coupon){
                $request->user()->newSubscription($plan->plan_name, $plan->stripe_id)->withCoupon($coupon->stripe_id)->create($request->payment_method_id);
            }else{
                $request->user()->newSubscription($plan->plan_name, $plan->stripe_id)->create($request->payment_method_id);
            }
            Session::forget('coupon_code');
        }catch(\Exception $e){
            session(['show_failed' => true]);
            Log::debug('Error in creating subscription : '.$e->getMessage());

            $request->session()->flash('error', $e->getMessage());
            return response()->json([
                'error' => true,
                'redirect_url' => route('manage.billing')
            ]);
        }

        // update user credits
        Credit::create([
            'user_id'           =>  $user->id, 
            'plan_id'           =>  $plan->id, 
            'search_credits'    =>  $plan->plan_credits, 
            'expiry_date'       =>  Carbon::now()->format('Y-m-d H:i:s')
        ]);
        
        $request->session()->flash('success', 'Subscription purchased successfully!');
        session(['show_success' => true]);
        return response()->json([
            'error' => false,
            'redirect_url' => route('subscription.success')
        ]);
    }

    public function subscriptionSuccess(Request $request)
    {
        $show_success = session('show_success');
        if(!$show_success){
            return redirect()->route('search');
        }

        $subscription_id = $request->get('subscription_id');
        if($subscription_id){
            $subscription = Subscription::where('paypal_id',$subscription_id)->first();     
            $subscription->fill(['paypal_status' => 'active'])->update(); 
        }
        
        $request->session()->forget('show_success');
        return view('user-admin.subscription.success');
    }

    public function failed(Request $request)
    {
        $show_failed = session('show_failed');
        if(!$show_failed){
            return redirect()->route('search');
        }
        
        $request->session()->forget('show_failed');
        return view('user-admin.subscription.failed');
    }

    public function cancel($id,Request $request)
    {
        $user = Auth::user();

        $active_subscription = $user->subscriptions()->first();

        if($active_subscription->payment_method == 'paypal')
        {
            $paypal = new PaypalGateway();
            $paypal->provider->cancelSubscription($active_subscription->paypal_id, 'Canceled by the user');
        }else{
            $user->subscription($id)->cancel();
        }
        
        return redirect()->back()->with('success','Subscription cancelled successfully!');
    }

    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required'
        ]);

        if (!$validator->passes()) {
            return $this->error(implode("<br/>",$validator->errors()->all()));
        }

        // check if valid coupon code or not
        $coupon = Promo::where('voucher_code',$request->coupon_code)->first();
        if(!$coupon){
            return $this->error('Invalid coupon code');
        }

        if($coupon->stripe_id){
            $stripe = new StripeGateway();
            $cc = $stripe->getCoupon($coupon->stripe_id);

            if(!$cc->valid){
                return $this->error('Invalid coupon code');
            }
        }

        session(['coupon_code'=> $coupon->voucher_code]);

        return $this->success();
    }

    public function removeCoupon(Request $request)
    {
        Session::forget('coupon_code');

        return $this->success();
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

        Credit::create([
            'user_id'           =>  $user->id, 
            'plan_id'           =>  $plan->id, 
            'search_credits'    =>  $plan->plan_credits, 
            'expiry_date'       =>  Carbon::now()->format('Y-m-d H:i:s')
        ]);

        session(['show_success' => true]);
        return redirect($response['links'][0]['href']);
    }
}
