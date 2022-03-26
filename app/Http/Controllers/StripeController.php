<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        try {
            $user = auth()->user();
            if (is_null($user->stripe_id)) {
                $user->createAsStripeCustomer();
            }

            $payment = $user->charge(1000, $request->paymentMethodId);
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