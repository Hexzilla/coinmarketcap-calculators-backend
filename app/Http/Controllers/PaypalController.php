<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
Use App\User;
Use App\Transaction;
use Exception;
use Auth;

class PaypalController extends Controller
{
    protected $paypalClient;

    public function __construct()
    {
        $this->paypalClient = new PayPalClient([]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['status' => 'error', 'message' => $errors ], 400);
        }

        $amount = $request->input('amount');
        
        $this->paypalClient->setApiCredentials(config('paypal'));
        
        $token = $this->paypalClient->getAccessToken();
        $this->paypalClient->setAccessToken($token);
        
        $order = $this->paypalClient->createOrder([
            "intent"=> "CAPTURE",
            "purchase_units"=> [
                 [
                    "amount"=> [
                        "currency_code"=> "USD",
                        "value"=> $amount
                    ],
                     'description' => 'test'
                ]
            ],
        ]);
        
        /*$mergeData = array_merge($data, [
            'status' => 'pending',
            'vendor_order_id' => $order['id']]
        );
        
        DB::beginTransaction();
        Order::create($mergeData);
        DB::commit();*/
        
        return response()->json($order);

        /*$user = auth()->user();
        $price = $request->price;

        $provider = new PayPalClient([]);
        $provider->getAccessToken();

        $result = $provider->createOrder([
            "intent"=> "CAPTURE",
            "purchase_units"=> [
                0 => [
                    "amount"=> [
                        "currency_code"=> "USD",
                        "value"=> strval(round($price, 2))
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => route('prices'),
                "return_url" => route('payment_status')
            ] 
        ]);

        return response()->json([
            'success' => true,
            'orderId' => $result->id,
        ]);*/

        /*session()->put('Order_id_' . $user['id'], $result['id']);
        foreach ($result['links'] as $l) {
            if ($l['rel'] == 'approve') {
                return redirect($l['href']);
            }            
        }

        session()->flash('error', 'Some error occur, sorry for inconvenient.');
        return redirect(route('prices'));*/
    }

    public function capture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'order_id' => 'required',
            'payment_gateway_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['status' => 'error', 'message' => $errors ], 400);
        }

        $user_id = $request->input('user_id');
        $order_id = $request->input('order_id');
        $payment_gateway_id = $request->input('payment_gateway_id');

        $this->paypalClient->setApiCredentials(config('paypal'));
        
        $token = $this->paypalClient->getAccessToken();
        $this->paypalClient->setAccessToken($token);

        $result = $this->paypalClient->capturePaymentOrder($order_id);

        try {
            DB::beginTransaction();
            if ($result['status'] === "COMPLETED") {
                $transaction = new Transaction;
                $transaction->vendor_payment_id = $order_id;
                $transaction->payment_gateway_id  = $payment_gateway_id;
                $transaction->user_id = $user_id;
                $transaction->status = 'completed';
                $transaction->save();

                /*$order = Order::where('vendor_order_id', $order_id)->first();
                $order->transaction_id = $transaction->id;
                $order->status = 'completed';
                $order->save();*/
                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
        }
        return response()->json($result);

        /*$user = auth()->user();
        
        $orderId = session()->get('Order_id_' . $user['id']);
        
        $provider = new PayPalClient([]);
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($orderId);
        if ($response['status'] == 'COMPLETED') {
            return redirect(route('checkout'));
        } else {
            session()->flash('error', 'Payment failed.');
            return redirect(route('prices'));
        }*/
    }
}