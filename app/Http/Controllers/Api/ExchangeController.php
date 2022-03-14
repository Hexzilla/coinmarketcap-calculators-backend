<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ExchangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */ 
    public function index(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'convert_id' => 'required|integer',
            'id' => 'required|integer',
        ]);
      
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['status' => 'error', 'message' => $errors ], 400);
        }
  
        $amount = $request->input('amount');
        $convert_id = $request->input('convert_id');
        $id = $request->input('id');
        
        $url = 'https://pro-api.coinmarketcap.com/v2/tools/price-conversion';
        $parameters = [
            'amount' => $amount,
            'convert_id' => $convert_id,
            'id' => $id
        ];

        $headers = [
            'Accepts: application/json',
            'X-CMC_PRO_API_KEY: b0e60f42-accf-4a2f-8137-47a4b3e40f3c'
        ];
        $qs = http_build_query($parameters); // query string encode the parameters
        $request = "{$url}?{$qs}"; // create the request URL


        $curl = curl_init(); // Get cURL resource
        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request,            // set the request URL
            CURLOPT_HTTPHEADER => $headers,     // set the headers 
            CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
        ));

        $response = curl_exec($curl); // Send the request, save the response
        $info = curl_getinfo($curl);
        curl_close($curl); 
        $status = 'success';
        if($info["http_code"] != 200){
            $status = 'error';
        }
        return response()->json(['status' => $status, 'data' => json_decode($response)]); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */ 
    public function priceHistory(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
      
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['status' => 'error', 'message' => $errors ], 400);
        }

        $id = $request->input('id');

        $url = 'https://influencermarketinghub.com/wp-admin/admin-ajax.php';
        $parameters = [
            'action' => 'get_crypto_data',
            'file_name' => "{$id}.json",
        ];
        
        $headers = [
            'origin: https://influencermarketinghub.com',
            'referer: https://influencermarketinghub.com/crypto-profit-calculator/',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36',
        ];
        $request = $url;

        $curl = curl_init(); // Get cURL resource

        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request,            // set the request URL
            CURLOPT_HTTPHEADER => $headers,     // set the headers 
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
        ));

        $response = curl_exec($curl); // Send the request, save the response
        $info = curl_getinfo($curl);
        curl_close($curl); 
        
        $status = 'success';
        if($info["http_code"] != 200){
            $status = 'error';
        }
        return response()->json(['status' => $status, 'data' => json_decode($response)]); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
