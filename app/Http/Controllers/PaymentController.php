<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
Use App\Models\User;
use Exception;
use Auth;

class PaymentController extends Controller
{
    public function index()
    {
        
    }

    public function payment(Request $request) {
        $userdata = auth()->user();
        $price = $request->price;

        // pay with paypal ---------------------------------------
        $provider = new PayPalClient([]);
        $provider->getAccessToken();

        $result = $provider->createOrder([
            "intent"=> "CAPTURE",
            "purchase_units"=> [
                0 => [
                    "amount"=> [
                        "currency_code"=> "EUR",
                        "value"=> strval(round($price,2))
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('prices'),
                "return_url" => route('payment_status')
            ] 
        ]);

        session()->put('Order_id_'.$userdata['id'], $result['id']);
        foreach ($result['links'] as $l) {
            if ($l['rel'] == 'approve') {
                return redirect($l['href']);
            }            
        }

        session()->flash('error', 'Some error occur, sorry for inconvenient.');
        return redirect(route('prices'));
      
    }

    public function paymentStatus(Request $request){
        $userdata = auth()->user();
        
        // paypal status --------------------------------------
        $orderID = session()->get('Order_id_'.$userdata['id']);
        $provider = new PayPalClient([]);
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($orderID);
        if ($response['status'] == 'COMPLETED') {
            return redirect(route('checkout'));
        } else{
            session()->flash('error', 'Payment failed.');
            return redirect(route('prices'));
        }
    }
}