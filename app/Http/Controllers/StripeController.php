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

class StripeController extends Controller
{
    use ApiResponder;

    public function index()
    {
        
    }

    public function payments()
    {
        $payments = Subscription::orderBy('id','desc')->paginate(10);
        return view('admin.payment', compact('payments'));
    }

    /*public function refund(Request $request)
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
    }*/

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
        if ($active_subscription) {
            if($active_subscription->stripe_plan == $plan->stripe_id && $active_subscription->stripe_status == 'active'){
                $request->session()->flash('error', 'You are already subscribed to this plan');
                return response()->json([
                    'error' => true,
                    'redirect_url' => route('manage.billing')
                ]);
            }
        }

        try{
            $request->user()->newSubscription($plan->name, $plan->stripe_id)->create($request->payment_method_id);
        }
        catch(\Exception $e){
            session(['show_failed' => true]);
            Log::debug('Error in creating subscription : '.$e->getMessage());

            $request->session()->flash('error', $e->getMessage());
            return response()->json([
                'error' => true,
                'redirect_url' => route('manage.billing')
            ]);
        }
        
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
}
