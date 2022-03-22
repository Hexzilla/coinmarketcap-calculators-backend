<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\StripeGateway;
use Carbon\Carbon;
use App\Promo;

class PromoController extends Controller
{
    public function index()
    {
        $items = Promo::paginate(10);
        return view('admin.promos.default', compact('items'));
    }

    public function create()
    {
        return view('admin.promos.create');
    }

    public function edit($id)
    {
        $data['item'] = Promo::findOrFail($id);
        return view('admin.promos.edit',$data);
    }

    public function store(Request $request)
    {
        //form validation
        $this->validate($request, [
            'title' => 'required|unique:promo',
            'voucher_code' => 'required',
            'discount_type' => 'required',
            'discount_amount' => 'required|numeric',
            'expiry_date' => 'nullable|date'
        ]);

        $promo = new Promo();
        $promo->title = $request->input('title');
        $promo->voucher_code = $request->input('voucher_code');
        $promo->discount_type = $request->input('discount_type');
        $promo->discount_amount = $request->input('discount_amount');
        $promo->expiry_date = Carbon::parse($request->input('expiry_date'))->format('Y-m-d');  
        $promo->save();

        // sync plan with stripe
        $stripe = new StripeGateway();
        $r = $stripe->createCoupon($promo);

        if($r){
            return redirect('/promo')->with('success', 'Promo code added');
        }else{
            return redirect('/promo');
        }
    }

    public function update(Request $request)
    {
        $id = $request->get('id');
        $this->validate($request, [
            'title' => 'required|unique:promo,title,'.$id,
            'voucher_code' => 'required',
        ]);

        $promo = Promo::findOrFail($id);
    
        $promo->title = $request->input('title');
        $promo->voucher_code = $request->input('voucher_code'); 
        $promo->save();

        return redirect(route('promo.index'))->with('success', 'Promo code details updated successfully!');
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        
        $promo = Promo::find($id);

        $stripe_id = $promo->stripe_id;

        $promo->delete();

        if($stripe_id){
            $stripe = new StripeGateway();
            $stripe->deleteCoupon($stripe_id);
        }
        return redirect('/promo')->with('success', 'Promo code removed');
    }
}
