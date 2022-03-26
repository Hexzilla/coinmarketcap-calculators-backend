<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
Use App\User;
use Exception;
use Auth;

class StripeController extends Controller
{
    public function __construct()
    {
        //Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function index()
    {
        
    }

    public function singleCharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'paymentMethodId' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['status' => 'error', 'message' => $errors ], 400);
        }

        try {
            $user = auth()->user();
            if (is_null($user->stripe_id)) {
                $user->createAsStripeCustomer();
            }

            $amount = $request->input('amount') * 100;
            $payment = $user->charge($amount, $request->paymentMethodId);
            if ($payment) {
                $client_secret = $payment->client_secret;
                return response()->json([
                    'success' => true,
                    'transactioId' => $client_secret->id,
                    'amount' => $client_secret->amount / 100,
                ]);
            }

            return response()->json([
                'success' => false,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e
            ]);
        }
    }
}