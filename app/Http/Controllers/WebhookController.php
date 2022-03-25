<?php

namespace App\Http\Controllers;
use Srmklive\PayPal\Traits\PayPalAPI\WebHooksVerification;
use Illuminate\Http\Request;
use App\Utils\PaypalGateway;
use App\Subscription;
use Log;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    use WebHooksVerification;
    
    public function index(Request $request)
    {
        $paypal = new PaypalGateway();

        $requestBody = $request->all();

        $payload = [
            'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO', null),
            'cert_url'          => $request->header('PAYPAL-CERT-URL', null),
            'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID', null),
            'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG', null),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME', null),
            'webhook_id'        => config('paypal.webhook_id'),
            'webhook_event'     => $requestBody,
        ];

        $d = $paypal->provider->getAccessToken();

        if (config('paypal.mode') == 'sandbox') {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$d['access_token']
            ])->post('https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature', $payload);
        }
        else {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$d['access_token']
            ])->post('https://api-m.paypal.com/v1/notifications/verify-webhook-signature', $payload);
        }

        $response = json_decode($response, true);
        if (!isset($response['verification_status'])) {
            return response()->json([
                'status' => 'error',
                'message' => $response,
            ]);
        }

        if ($response['verification_status'] != 'SUCCESS') {
            return response()->json([
                'status' => 'error',
                'message' => $response,
            ]);
        }

        $this->eventHandler($requestBody);
    }

    public function eventHandler($context)
    {
        switch($context['event_type'])
        {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                    $resource = $context['resource'];
                    $subscription_id = $resource['id'];
                    $subscription = Subscription::where('paypal_id', $subscription_id)->first();
                    if ($subscription) {
                        $subscription->fill(['paypal_status' => 'active'])->save();
                    }
                break;

            case 'BILLING.SUBSCRIPTION.EXPIRED':
                    $resource = $context['resource'];
                    $subscription_id = $resource['id'];
                    $subscription = Subscription::where('paypal_id', $subscription_id)->first();
                    if ($subscription) {
                        $subscription->fill(['paypal_status' => 'expired'])->save();
                    }
                break;

            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                    $resource = $context['resource'];
                    $subscription_id = $resource['id'];
                    $subscription = Subscription::where('paypal_id', $subscription_id)->first();
                    if ($subscription) {
                        $subscription->fill(['paypal_status' => 'failed'])->save();
                    }
                break;

            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    $resource = $context['resource'];
                    $subscription_id = $resource['id'];
                    $subscription = Subscription::where('paypal_id', $subscription_id)->first();
                    if ($subscription) {
                        $subscription->fill(['paypal_status' => 'suspended'])->save();
                    }
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                    $resource = $context['resource'];
                    $subscription_id = $resource['id'];
                    $subscription = Subscription::where('paypal_id', $subscription_id)->first();
                    if ($subscription) {
                        $subscription->fill(['paypal_status' => 'cancelled'])->save();
                    }
                break;

            default:
                break;
        }
    }
}
