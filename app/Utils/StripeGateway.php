<?php 
namespace App\Utils;
use Carbon\Carbon;
use App\Plan;
use App\Promo;
use Log;

class StripeGateway
{
    public $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
    }

    public function createProduct(Plan $plan)
    {
        
        try{
            $product = $this->stripe->products->create([
                'name' => $plan->plan_name,
                'metadata' => $plan->toArray()
            ]);
            return $product->id;
        }catch(\Exception $e){
            $plan->delete();
            Log::error('Failed to create product : '.$e->getMessage());
            return;
        }
    }

    public function createPlan(Plan $plan)
    {
        // first create the product
        $product_id = $this->createProduct($plan);

        if(!$product_id)
        {
            return false;
        }

        try{
            $d = [
                'amount'    => $plan->plan_price*100,
                'currency'  => env('CASHIER_CURRENCY'),
                'interval'  => 'month',
                'product'   => $product_id,
            ];

            $stripePlan = $this->stripe->plans->create($d);

            // update the plan id in local db table
            $plan->fill(['stripe_id' => $stripePlan->id])->save();

            return $stripePlan->id;
        }catch(\Exception $e){
            $plan->delete();
            $this->deleteProduct($product_id);
            Log::error('Failed to create plan : '.$e->getMessage());
            return;
        }
    }

    public function deleteProduct($product_id)
    {
        try{
            $this->stripe->products->delete($product_id,[]);
            return true;
        }catch(\Exception $e){
            Log::error('Failed to delete product : '.$e->getMessage());
            return false;
        }
    }

    public function deletePlan($plan_id)
    {
        // get the product of the plan
        $plan = $this->stripe->plans->retrieve($plan_id,[]);
        try{
            $this->stripe->plans->delete($plan_id,[]);

            // delete the product too
            $this->deleteProduct($plan->product);

            return true;
        }catch(\Exception $e){
            Log::error('Failed to delete plan : '.$e->getMessage());
            return false;
        }
    }

    public function createCoupon(Promo $item)
    {
        try{
            $start_date = Carbon::now();
            $end_date = Carbon::parse($item->expiry_date);
            $duration_in_months = $end_date->diffInMonths($start_date);

            if($item->discount_type == 'fixed'){
                $d = [
                    'name' => $item->title,
                    'amount_off' => (float)$item->discount_amount,
                    'currency' => env('CASHIER_CURRENCY'),
                    'duration' => 'once',
                ];
            }else{
                $d = [
                    'name' => $item->title,
                    'percent_off' => (float)$item->discount_amount,
                    'duration' => 'once',
                ];
            }

            $coupon = $this->stripe->coupons->create($d);

            // update the plan id in local db table
            $item->fill(['stripe_id' => $coupon->id])->save();

            return $coupon->id;
        }catch(\Exception $e){
            $item->delete();
            Log::error('Failed to create promo code : '.$e->getMessage());
            session()->flash('error', 'Failed to create promo code : '.$e->getMessage());
            return;
        }
    }

    public function deleteCoupon($id)
    {
        // get the product of the plan
        try{
            $this->stripe->coupons->delete($id,[]);
            return true;
        }catch(\Exception $e){
            Log::error('Failed to delete coupon : '.$e->getMessage());
            return false;
        }
    }

    public function getCoupon($id)
    {
        // get the product of the plan
        try{
            return $this->stripe->coupons->retrieve($id,[]);
        }catch(\Exception $e){
            Log::error('Failed to get coupon : '.$e->getMessage());
            return false;
        }
    }

    public function deleteCustomer($id)
    {
        // get the product of the plan
        try{
            $this->stripe->customers->delete($id,[]);
            return true;
        }catch(\Exception $e){
            Log::error('Failed to delete customer : '.$e->getMessage());
            return false;
        }
    }
}
