<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganizationSubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $user->load('organization.subscription');
        if ($user->organization != null && $user->organization->subscription != null) {
            return redirect(route('organization.home'));
        }
//        return $user;
        $plans = [];
        return view('subscription')->with('plans', $plans);
    }

    public function subscriptionBuy(Request $request)
    {

        DB::beginTransaction();
        try {
            $user = $request->user();

            $user->load('organization.subscription');
//            return $user;
            if (!$user->organization) {
                return redirect()->back()->withErrors(['error' => "Sorry, that user is invalid"]);
            }
//        return $user->organization;
            $user->organization->payment_style_id = $request->recurring;
            $plan = SubscriptionPlan::find($request->plan_id);
            if (!$plan) {
                return redirect()->back()->withErrors(['error' => "Sorry, that plan is invalid"]);
            }

            $priceId = $plan->stripe_monthly_plan_id;
            if ($request->recurrin == 1)
                $priceId = $plan->stripe_recurring_plan_id;

            if ($user->organization->stripe_customer_id == null) {
                $stripe = new \Stripe\StripeClient(
                    env('STRIPE_SECRET_KEY')
                );
                $data = $stripe->customers->create([
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
                $user->organization->stripe_customer_id = $data->id;
            }
            $user->organization->save();
            $OrganizationSubscription = OrganizationSubscription::create([
                "status" => 4,
                "organization_id" => $user->organization->id,
                "subscription_plan_id" => $plan->id,
                'plan_name' => $plan->plan_name,
                'plan_price' => $plan->plan_price,
                'plan_months' => $plan->plan_months,
                'plan_stripe_id' => $plan->stripe_plan_id,
                'plan_stripe_price_id' => $priceId,
            ]);


            $payment = [
                'success_url' => route('organization.subscribe.success') . '?session_id=' . $OrganizationSubscription->id,
                'cancel_url' => route('organization.subscribe.fail') . '?session_id=' . $OrganizationSubscription->id,
                'mode' => 'payment',  //subscription
                'customer' => $user->organization->stripe_customer_id,
                'line_items' => [[
                    'price' => $priceId,
                    // For metered billing, do not pass quantity
                    'quantity' => 1,
                ]],
            ];
            if ($request->recurrin == 1)
                $payment['mode'] = 'subscription';
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $session = \Stripe\Checkout\Session::create($payment);
//            dd($session);
            $OrganizationSubscription->session_id = $session->id;
            $OrganizationSubscription->save();
            DB::commit();
            return redirect($session->url);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => "Oops, something went wrong. Please try again!"]);
            DB::rollback();
            // something went wrong
        }

    }

    public function getSessionDetails($session_id, $OrganizationSubscription, $type = 'monthly', $trail = 0)
    {
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET_KEY')
        );
        $return = $stripe->checkout->sessions->retrieve(
            $session_id,
            []
        );

        if ($return->status == 'open') {
            $stripe->checkout->sessions->expire(
                $session_id,
                []
            );
        }
        $OrganizationSubscription->transaction_status = ($return->payment_status == 'paid' ? 2 : 0);
        if ($return->payment_status == 'paid') {
            $sub = $stripe->subscriptions->retrieve(
                $return->subscription,
                []
            );
            $OrganizationSubscription->transaction_id = $sub->latest_invoice;
            $OrganizationSubscription->stripe_subscriptions_id = $sub->id;
            $now = Carbon::parse($sub->current_period_start);
            $end = Carbon::parse($sub->current_period_end);
//            $now = Carbon::now();
//
//
//            if ($type == "monthly") {
//                $end = Carbon::now()->month();
//            } else {
//                $end = Carbon::now()->addYear();
//            }
//            $trail=2;
            if ($trail > 0) {
//                $now = $now->addDays($trail);
                $end = $end->addDays($trail);
            }

//            dd($now, $end);
            $OrganizationSubscription->start_time = $now;

            $OrganizationSubscription->end_time = $end;
            $OrganizationSubscription->status = 2;
        } else {
            $OrganizationSubscription->status = 0;
        }
        $OrganizationSubscription->save();
        return $return;
    }

    public function updateSubscriptions($OrganizationSubscription, $getSessionDetails)
    {

    }

    public function subscriptionFail(Request $request)
    {

        $user = $request->user();
        $user->load('organization');
        $OrganizationSubscription = OrganizationSubscription::where('organization_id', $user->organization->id)->where('id', $request->session_id)->first();
        $data = $this->getSessionDetails($OrganizationSubscription->session_id, $OrganizationSubscription);

        $user->load('organization.subscription');
//        return [$user,$data,$OrganizationSubscription];
        return view('subscription-success')->with('fail', 1)->with('user', $user)->with('payment', $data)->with('subscription', $OrganizationSubscription);
    }

    public function subscriptionSuccess(Request $request)
    {


        if (empty($request->session()->has("users"))) {
            return redirect(route('organization.home'));
        }
        $data = $request->session()->get("users");
        $plan = SubscriptionPlan::find($data['plan']);
        $OrganizationSubscription = OrganizationSubscription::where('email', $data['email'])->where('id', $request->session_id)->first();
//        dd($plan->toarray());
        $Subscription = $this->getSessionDetails($OrganizationSubscription->session_id, $OrganizationSubscription, $data['type'], $plan->trail);
        if ($Subscription->payment_status == "paid") {
            OrganizationSubscription::where('email', $data['email'])->where('transaction_status', 1)->delete();
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => 2,
                'status' => 1
            ]);

            $ord = Organization::create([
                'name' => $data['organization_name'],
                'phone' => $data['phone'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code'],
                'country' => $data['country'],
//            'build_year' => $data['build_year'],
//            'no_of_instructor' => $data['no_of_instructor'],
                'user_id' => $user->id,
            ]);
            $OrganizationSubscription->organization_id = $ord->id;
            $OrganizationSubscription->save();
            dispatch(new \App\Jobs\SendEmailJob(['type' => "welcome", 'email' => $data['email'],'name'=>$user->name]));
            Auth::login($user);
            $request->session()->flash("users");
        }
        $user = $request->user();
        if ($user) {
            $user->load('organization');
            $user->load('organization.subscription');
            $OrganizationSubscription->plan_price = $data['type'] == "yearly" ? $plan->plan_yearly_price : $plan->plan_monthly_price;
        }
        $view = view('subscription-success')->with('user', $user)->with('payment', $Subscription)->with('subscription', $OrganizationSubscription);
        if ($request->success == 1) {
//            $view = $view->with('success', 1)->with('type',$data['type'] == "yearly" ?"Year":"Month");
            $request->session()->flash('paymentsuccess', '<b>Great!</b> Thanks for Signing up for the free trial of the Marro App ' . $plan->plan_name . " Plan!");
            return redirect(route('organization.home'));
        } else {
            $view = $view->with('fail', 1);
        }
        return $view;
//        dd($data);
//
//        die;
//        $user = $request->user();
//        $user->load('organization');
//        $OrganizationSubscription = OrganizationSubscription::where('organization_id', $user->organization->id)->where('id', $request->session_id)->first();
//        $data = $this->getSessionDetails($OrganizationSubscription->session_id, $OrganizationSubscription);
//        $user->load('organization.subscription');
////        return [$user,$data];
//        return view('subscription-success')->with('success', 1)->with('user', $user)->with('payment', $data)->with('subscription', $OrganizationSubscription);
    }
}
