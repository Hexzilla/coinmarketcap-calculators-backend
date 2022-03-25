<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
Use App\Models\User;
use Exception;
use Auth;

class StripeController extends Controller
{
    public function index()
    {
        
    }

    public function clientSecret(Request $request)
    {
        $user = auth()->user();
        $intent = $user->createSetupIntent();
        return response()->json([
            'intent' => $intent,
        ]);
    }
}