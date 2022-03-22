<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Hash;
use App\Utils\StripeGateway;
use App\Utils\PaypalGateway;
use App\User;
use App\Credit;
use App\Plan;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        // $users = User::find(2)->credit->search_credits;
        // dd($users);
        return view('admin.users', compact('users'));
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $user = User::find($id);

        $active_subscription = $user->subscriptions()->first();
        if($active_subscription){
            $user->subscription($active_subscription->name)->cancel();
        }

        if($user->stripe_id){
            $stripe = new StripeGateway();
            $stripe->deleteCustomer($user->stripe_id);
        }

        $user->delete();
        return redirect('/users')->with('success', 'User removed');
    }

    public function editProfile()
    {
        $user = Auth::user();
        $data['user'] = $user;
        $data['email'] = $user->email;

        $active_subscription = $user->subscriptions()->first();

        $data['active_subscription'] = $active_subscription;

        if($active_subscription)
        {
            if($active_subscription->payment_method == 'paypal')
            {
                if($active_subscription->paypal_status == 'active'){
                    $data['active_plan'] = Plan::where('stripe_id',$active_subscription->stripe_plan)->first();
                }else{
                    $data['active_plan'] = '';
                }
            }else{
                if($active_subscription->stripe_status == 'active'){
                    $data['active_plan'] = Plan::where('stripe_id',$active_subscription->stripe_plan)->first();
                }else{
                    $data['active_plan'] = '';
                }
            }
        }
        
    
        return view('user-admin.manage-account', $data);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
            'email' => ['required'],
        ]);
   
        User::find(auth()->user()->id)->update([
            'email' => $request->email,
            'password'=> Hash::make($request->new_password)
        ]);

        //dd('Password change successfully.');
        return redirect()->route('edit.profile')->with('success', 'Password change successfully.');
  
    }

    public function newPlan($userId)
    {
        $user = User::find($userId);
        if (empty($user)) {
            return redirect()->back()->with('error', 'Invalid User ID.');
        }

        $items = Plan::select(['id', 'plan_name'])->get();

        $plans = array();
        foreach ($items as $item) {
            $plans[$item['id']] = $item['plan_name'];
        }

        return view('admin.users.assign-plan', [
            'plans' => $plans,
            'userId' => $userId,
        ]);
    }

    public function addPlan(Request $request, $userId)
    {
        $user = User::find($userId);
        if (empty($user)) {
            return redirect()->back()->with('error', 'Invalid User ID.');
        }

        $request->validate([
            'plan_id' => ['required'],
            'expiry_date' => ['required'],
        ]);

        $plan = Plan::find($request->plan_id);
        if (empty($plan)) {
            return redirect()->back()->with('error', 'Invalid Plan ID.');
        }

        // Check if the user has already the plan
        $creditItem = Credit::where('user_id', $userId);
        if($creditItem->get()->count() > 0){
            $creditItem->update([
                'plan_id' => $request->plan_id,
                'expiry_date' => $request->expiry_date,   
            ]);

            return redirect()->back()->with('success', 'Plan is assigned successfully.');
        }

        Credit::create([
            'user_id' => $userId,
            'plan_id' => $request->plan_id,
            'search_credits' => $plan['plan_credits'],
            'expiry_date' => $request->expiry_date,            
        ]);

        return redirect()->back()->with('success', 'Plan is assigned successfully.');
    }


    public function newCredits($userId)
    {
        $user = User::find($userId);
        if (empty($user)) {
            return redirect()->back()->with('error', 'Invalid User ID.');
        }

        $credits = Credit::select('search_credits')->where('user_id', $userId)->get();
        if (empty($credits)) {
            return redirect()->back()->with('error', 'Invalid Credit info.');
        }

        return view('admin.users.updateCredits', [
            'userId' => $userId,
            'email' => $user->email,
            'credits' => $credits[0]->search_credits,
        ]);
    }

    public function addCredits(Request $request, $userId)
    {
        $user = User::find($userId);
        if (empty($user)) {
            return redirect()->back()->with('error', 'Invalid User ID.');
        }

        $request->validate([
            'email' => 'required',
            'credits' => 'required|integer',
        ]);

        $search_credits = $request->input('credits');
        if ($search_credits < 0) {
            return 'Insufficient credits';
        }

        Credit::where('user_id', $userId)->update(['search_credits' => $search_credits]);

        return redirect()->back()->with('success', 'Credits are added successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        $active_subscription = $user->subscriptions()->first();

        


        if($active_subscription){
            if($active_subscription->payment_method == 'paypal')
            {
                $paypal = new PaypalGateway();
                $paypal->provider->cancelSubscription($active_subscription->paypal_id, 'Canceled by the user');
            }else{
                $user->subscription($active_subscription->name)->cancel();
            }
        }

        if($user->stripe_id){
            $stripe = new StripeGateway();
            $stripe->deleteCustomer($user->stripe_id);
        }

        $user->delete();
        $request->session()->flash('success', 'Account deleted successfully!');
        return redirect('/search');
    }
}
