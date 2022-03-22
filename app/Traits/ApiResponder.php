<?php

namespace App\Traits;

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Api Responser Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponder
{
	/**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     * @param  string  $message
     * @param  int|null  $code
     * @return \Illuminate\Http\JsonResponse
     */
	protected function success($data = [], string $message = 'success', int $code = 200)
	{
		if(isset($data['html']))
		{
			$html = $data['html'];
			unset($data['html']);
		}else{
			$html = '';
		}
		
		return response()->json([
			'error'    	=> false,
			'message'   => $message,
			'data'      => $data,
			'html'		=> $html
		], $code);
	}

	/**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|string|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
	protected function error(string $message = 'error', int $code = 200, $data = null)
	{
		if(isset($data['html']))
		{
			$html = $data['html'];
			unset($data['html']);
		}else{
			$html = '';
		}

		return response()->json([
			'error'    	=> true,
			'message'   => $message,
			'data'      => $data,
			'html'		=> $html
		], $code);
	}

}