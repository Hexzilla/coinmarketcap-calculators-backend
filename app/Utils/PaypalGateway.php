<?php 
namespace App\Utils;
use Srmklive\PayPal\Services\PayPal as PayPalClient;


use Carbon\Carbon;
use App\Plan;
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

    public function createSubscription($plan, $user)
    {
        $response = $this->provider->addProduct($plan->name, $plan->name, 'SERVICE', 'SOFTWARE')
            ->addMonthlyPlan($plan->name, $plan->name, $plan->price)
            ->setReturnAndCancelUrl(route('subscription.success'), route('manage.billing'))
            ->setupSubscription($user->name, $user->email, Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'));

        return $response;
    }

    public function verifyWebHook($data)
    {
        return $this->provider->verifyWebHook($data);
    }
}