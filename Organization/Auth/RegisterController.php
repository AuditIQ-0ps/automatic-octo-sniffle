<?php

namespace App\Http\Controllers\Organization\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::STUDENT_HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    //validation for input fields
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', "regex: /^([a-zA-Z0-9_\.\-+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/"],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'organization_name' => ['required'],
            'city' => ['required'],
            'state' => ['required'],
            'zip_code' => ['required'],
            'country' => ['required'],
//            'no_of_instructor' => ['required', 'integer'],
            'phone' => ['required'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\Models\User
     */
    //save new organization records
    protected function create(array $data)
    {
        $request = \request();
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET_KEY')
        );

        $plan = SubscriptionPlan::find($request->session()->get('plan'));
        if (!($plan)) {
            return redirect('pricing.html')->withErrors("Please select any plan.");
        }


//        dd($plan->toarray(), $priceID);
        $customers = $stripe->customers->create([
            'name' => $data['first_name'] . " " . $data['last_name'],
            'email' => $data['email'],
        ]);
        $data['stripe_customer_id'] = $customers->id;
//
        $request->session()->put("users", $data);
        return redirect(route('checkout.plan'));

//        dd();
//        dd($session);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 2,
            'status' => 1
        ]);

        Organization::create([
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
        return $user;

    }
    //validate records and redirect to create method
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        return $this->create($request->all());
    }

    //create subscription records
    public function SessionCheckout(Request $request)
    {
        $plan = SubscriptionPlan::find($request->session()->get('plan'));
        if (!($plan)) {
            return redirect('pricing.html')->withErrors("Please select any plan.");
        }
        if (!$request->session()->has('users')) {
            return redirect(route('organization.register'))->withErrors("Please fill the all details.");
        }
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET_KEY')
        );
        $data = $request->session()->get("users");
        $priceID = $plan->stripe_monthly_price_id;
        $price = $plan->plan_monthly_price;

        $month = 1;
        $extraPlan = [];
        $emp_price = 0;
        if ($request->session()->get('type') == 'yearly') {
            $price = $plan->plan_yearly_price;
            $priceID = $plan->stripe_yearly_price_id;
            $month = 12;
            if ($plan->extra) {
                $emp_price = $price * $request->session()->get('employees', 251);
                $extraPlan['price_id'] = $plan->extra['year']['stripe_price_id'];
            } else {
                $emp_price = $price;
            }

        } else {
            if ($plan->extra) {
                $emp_price = $price * $request->session()->get('employees', 251);
                $extraPlan['price_id'] = $plan->extra['month']['stripe_price_id'];
            }
            $emp_price = $price;
        }
        $OrganizationSubscription = OrganizationSubscription::create([
            "status" => 4,
            "email" => $data['email'],
            "subscription_plan_id" => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_price' => $emp_price,
            'plan_months' => $month,
            'plan_stripe_id' => $plan->stripe_plan_id,
            'plan_stripe_price_id' => $priceID,
        ]);
        $data['session_id'] = $OrganizationSubscription->id;
        $payment = [
            'success_url' => route('organization.subscribe.success') . '?success=1&session_id=' . $OrganizationSubscription->id,
            'cancel_url' => route('organization.subscribe.success') . '?success=0&session_id=' . $OrganizationSubscription->id,
            'mode' => 'subscription',  //subscription
            'customer' => $data['stripe_customer_id'],
            'line_items' => [[

            ]],

        ];
        $request->session()->put("users", $data);
        $payment['line_items'][0]['price'] = $priceID;
        if ($plan->type == "Flat") {
            $payment['line_items'][0]['price'] = $priceID;
            $payment['line_items'][0]['quantity'] = 1;
        } else {
            $payment['line_items'][1]['price'] = $extraPlan['price_id'];
            $payment['line_items'][1]['quantity'] = $request->session()->get('employees', 251);
        }
        if ($plan->trail > 0) {
            $payment['subscription_data']['trial_period_days'] = $plan->trail;// $plan->trail;
        }
        $session = $stripe->checkout->sessions->create($payment);
        $OrganizationSubscription->session_id = $session->id;
        $OrganizationSubscription->save();
        return redirect($session->url);
    }

    //show organization checkout page
    public function checkout(Request $request)
    {
        $plan = SubscriptionPlan::find($request->session()->get('plan'));
        if (!($plan)) {
            return redirect('pricing.html')->withErrors("Please select any plan.");
        }
        if (!$request->session()->has('users')) {
            return redirect(route('organization.register'))->withErrors("Please fill the all details.");
        }

        $data = $request->session()->get("users");
        $priceID = $plan->stripe_monthly_price_id;
        $price = $plan->plan_monthly_price;
        $month = "Month";
        $smonth = "mo";
//        dd($data);

//        dd($plan->toarray());
        if ($request->session()->get('type') == 'yearly') {
            $price = $plan->plan_yearly_price;
            $priceID = $plan->stripe_yearly_price_id;
            $month = "Year";
            $smonth = "yr";
        }
        $plan->price_slabel = $price . "/User/" . $smonth;
        $plan->price_label = $price . "/User/" . $month;
        $plan->price_cslabel = $price * $data['size'] . "/" . $smonth;
        $plan->price_clabel = $price * $data['size'] . "/" . $month;
        if ($plan->type == "Flat") {
            $plan->price_slabel = $plan->price_cslabel = $price . "/" . $smonth;
            $plan->price_label = $plan->price_clabel = $price . "/" . $month;
        }
        $plan->price = $price;
        $plan->size = $data['size'];
        $plan->type_text = ucfirst($request->session()->get('type'));
        return view('checkout')->with('plan', $plan);

    }

    //organization select plan while registration
    public function selectPlan(Request $request)
    {
        $plan = SubscriptionPlan::find($request->plan);
        if (!$plan) {
            return redirect()->back()->withErrors("Please select valid plan");
        }
        $valid = '';
        if ($plan->max_participants > 0) {
            $valid = "|max:" . $plan->max_participants;
        }
//        dd($valid);
        $request->validate([
            'type' => 'required|in:yearly,monthly',
            'employees' => 'required|integer|min:' . ($plan->min_participants + 1) . $valid

        ], [
            'employees.max' => 'The :attribute must not be greater than :max in ' . strtolower($plan['plan_name']) . " plan.",
            'employees.min' => 'The :attribute must be at least :min in ' . strtolower($plan['plan_name']) . " plan.",
            'type.in' => 'The selected recurring type is invalid.',
        ]);
        $request->session()->put('type', $request->type);
        $request->session()->put('employees', $request->employees);
        $request->session()->put('plan', $plan->id);
        return redirect('organization/register');
    }

    //show organization registration form
    public function showRegistrationForm(Request $request)
    {
        if (!($request->session()->has('plan') && $request->session()->has('employees') && $request->session()->has('type'))) {
            return redirect('pricing.html')->withErrors("Please select any plan.");
        }
        return view('organization.auth.register');
    }

}
