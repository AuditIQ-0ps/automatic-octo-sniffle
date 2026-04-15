<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $standard = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $plan = SubscriptionPlan::orderby($sortedBy, $sortedOrder);
        if (isset($data['name'])) {
            $plan = $plan->where('plan_name', 'like', '%' . $data['name'] . '%');
        }
        if ($request->price)
            $plan = $plan->where('plan_price',  $request->price );
        if ($request->month)
            $plan = $plan->where('plan_months', 'like', '%' . $request->month . '%');
        if ($request->from) {
            $plan = $plan->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $plan = $plan->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }
        $plan1 = clone $plan;
        $plan = $plan->paginate(10);
        $planCount = $plan1->count();

        return view('plan.index', compact('planCount', 'plan', 'userId', 'standard', 'sortedBy', 'sortedOrder'));
    }

    public function saveAjax(Request $request)
    {
//        dd(2);
        $data = $request->all();
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET_KEY')
        );
//        dd($data);
        if ($data['plan_id'] != 0) {
//             $data;
            $organization = SubscriptionPlan::find($data['plan_id']);
            if (!$organization) {
                $response['success'] = false;
                $response['message'] = 'Oops, we could not find that plan!';
                return response()->json($response);
            }
            $stripePlan = $stripe->products->update($organization->stripe_plan_id, [
                'name' => $data['plan_name'],
            ]);
            $stripe_recurring_plan = $stripe->prices->create([
                'unit_amount' => $data['plan_price'] * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => 'month', 'interval_count' => $data['plan_months']],
                'product' => $organization->stripe_plan_id,
            ]);
            $stripe_monthly_plan = $stripe->prices->create([
                'unit_amount' => $data['plan_price'] * 100,
                'currency' => 'usd',
//                'recurring' => ['interval' => 'month'],
                'product' => $organization->stripe_plan_id,
            ]);
            $data['stripe_monthly_plan_id'] = $stripe_monthly_plan->id;
            $data['stripe_recurring_plan_id'] = $stripe_recurring_plan->id;
            $organization->update($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Plan updated successfully!';
            return response()->json($response);
        } else {

            $stripePlan = $stripe->products->create([
                'name' => $data['plan_name'],
            ]);
            $data['stripe_plan_id'] = $stripePlan_id = $stripePlan->id;

            $stripe_recurring_plan = $stripe->prices->create([
                'unit_amount' => $data['plan_price'] * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => 'month', 'interval_count' => $data['plan_months']],
                'product' => $stripePlan_id,
            ]);
            $stripe_monthly_plan = $stripe->prices->create([
                'unit_amount' => $data['plan_price'] * 100,
                'currency' => 'usd',
//                'recurring' => ['interval' => 'month'],
                'product' => $stripePlan_id,
            ]);
            $data['stripe_monthly_plan_id'] = $stripe_monthly_plan->id;
            $data['stripe_recurring_plan_id'] = $stripe_recurring_plan->id;
            $results = SubscriptionPlan::create($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Plan saved successfully!';
            return response()->json($response);
        }
    }

    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
        $results = SubscriptionPlan::find($data['id']);
//        dd($results);
        $response = [];
        $response['success'] = false;
        $response['message'] = '';

        if (!empty($results['id']) && $results['id'] > 0) {
            $response['success'] = true;
            $response['message'] = '';
            $response['data'] = $results;
        }
        return response()->json($response);
    }

    public function delete(Request $request)
    {
        $data = $request->all();
        if (empty($data['id'])) {
            return abort(404);
        }
        $isDelete = Subject::where('id', $data['id'])->delete();
        if ($isDelete) {
            $request->session()
                ->flash('success', 'Subject deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the subject was not deleted. Please try again.');
        }
        return Redirect::back();
    }
}
