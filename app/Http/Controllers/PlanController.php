<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Plan;
use App\Utils\StripeGateway;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::paginate(10);
        return view('admin.plans.default', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function edit($id)
    {
        $data['item'] = Plan::findOrFail($id);
        return view('admin.plans.edit',$data);
    }

    public function store(Request $request)
    {
        //form validation
        $this->validate($request, [
            'plan_name' => 'required|unique:plans',
            'plan_price' => 'required|integer',
            'plan_credits' => 'required|integer'
        ]);

        $plan = new Plan();
        $plan->plan_name = $request->input('plan_name');
        $plan->plan_price = $request->input('plan_price');
        $plan->plan_credits = $request->input('plan_credits');
        $plan->save();

        // sync plan with stripe
        $stripe = new StripeGateway();
        $stripe->createPlan($plan);

        return redirect('/plans')->with('success', 'New plan created');
    }

    public function update(Request $request)
    {
        $id = $request->get('id');
        $this->validate($request, [
            'plan_name' => 'required|unique:plans,plan_name,'.$id,
            'plan_credits' => 'required|integer'
        ]);

        $plan = Plan::findOrFail($id);
    
        $plan->plan_name = $request->input('plan_name');
        $plan->plan_credits = $request->input('plan_credits');
        $plan->save();

        return redirect(route('plans.index'))->with('success', 'Plan details updated successfully!');
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $plan = Plan::find($id);

        $plan_id = $plan->stripe_id;
        
        $plan->delete();

        // delete the plan from stripe too$stripe = new StripeGateway();
        if($plan_id){
            $stripe = new StripeGateway();
            $stripe->deletePlan($plan_id);
        }

        return redirect('/plans')->with('success', 'Plan removed');
    }
}
