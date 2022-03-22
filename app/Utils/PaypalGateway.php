<?php 
namespace App\Utils;
use Srmklive\PayPal\Services\PayPal as PayPalClient;


use Carbon\Carbon;
use App\Plan;
use App\Promo;
use Log;

class PaypalGateway
{
    
    public $provider;
    public function __construct()
    {
        $provider = new PayPalClient;

        $this->provider = \PayPal::setProvider();
        $this->provider->getAccessToken();
    }

    public function createSubscription($plan,$user,$coupon = null)
    {
        if($coupon)
        {
            $date = Carbon::parse($coupon->expiry_date);
            $now = Carbon::now();

            $diff = $date->diffInDays($now);
            $diff = $diff ? $diff : 1;
            $response = $this->provider->addProduct($plan->plan_name, $plan->plan_name, 'SERVICE', 'SOFTWARE')
            ->addPlanTrialPricing('DAY', $diff)
            ->addMonthlyPlan($plan->plan_name, $plan->plan_name, $plan->plan_price)
            ->setReturnAndCancelUrl(route('subscription.success'), route('manage.billing'))
            ->setupSubscription($user->fname.' '.$user->lname, $user->email, Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'));
        }else{
            $response = $this->provider->addProduct($plan->plan_name, $plan->plan_name, 'SERVICE', 'SOFTWARE')
            ->addMonthlyPlan($plan->plan_name, $plan->plan_name, $plan->plan_price)
            ->setReturnAndCancelUrl(route('subscription.success'), route('manage.billing'))
            ->setupSubscription($user->fname.' '.$user->lname, $user->email, Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'));
        }

        return $response;
    }

    public function verifyWebHook($data)
    {
        return $this->provider->verifyWebHook($data);
    }
}