<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Cashier\Events\WebhookReceived;
use App\Subscription;
use Log;

class StripeEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        Log::debug('here it is');
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        if ($event->payload['type'] === 'customer.subscription.updated') {
            // Handle the incoming event...
            
            $payment_callback = $event->payload['data']['object'];

            if ($payment_callback['status'] != 'active') {
                $subscription = Subscription::where('stripe_id',$payment_callback->id)->first();

                $subscription->fill(['ends_at' => $payment_callback['cancel_at'],'stripe_status' => $payment_callback['status']])->save();
            }
        }
    }
}
