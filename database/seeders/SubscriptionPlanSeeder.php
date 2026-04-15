<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubscriptionPlan::truncate();
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET_KEY')
        );
        $plans = [
            [
                'plan_name' => "Basic",
                'stripe_plan_id' => null,
                'plan_yearly_price' => 199,
                'plan_monthly_price' => 19.99,
                'type' => 'Flat',
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'meeting_time' => 30,
                'min_participants' => 0,
                'max_participants' => 100,
                'storage' => 5,
                'trail' => 30
            ],
            [
                'plan_name' => "Pro",
                'stripe_plan_id' => null,
                'plan_yearly_price' => 499,
                'plan_monthly_price' => 49.99,
                'type' => 'Flat',
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'meeting_time' => 30 * 60,
                'min_participants' => 50,
                'max_participants' => 250,
                'storage' => 10,
                'trail' => 30
            ],
            [
                'plan_name' => "Enterprise",
                'stripe_plan_id' => null,
                'plan_yearly_price' => 55,
                'plan_monthly_price' => 5,
                'type' => 'Par User',
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'meeting_time' => -1,
                'min_participants' => 250,
                'max_participants' => -1,
                'storage' => 20,
                'trail' => 30
            ]

        ];
        foreach ($plans as &$plan) {
            $stripePlan = $stripe->products->create([
                'name' => $plan['plan_name'],
                'unit_label' => 'User'
            ]);

            $recurring = [];
            if ($plan['type'] == "Par User") {
                $stripe_monthly_plan = $stripe->prices->create([
                    'nickname' => $plan['plan_name'] . " Monthly Advance",
                    'unit_amount' => $plan['plan_monthly_price'] * 100,
                    'currency' => 'usd',
                    'recurring' => ['interval' => 'month'],
                    'product' => $stripePlan->id,
                ]);
                $stripe_yearly_plan = $stripe->prices->create([
                    'nickname' => $plan['plan_name'] . " Yearly Advance",
                    'unit_amount' => $plan['plan_yearly_price'] * 100,
                    'currency' => 'usd',
                    'recurring' => ['interval' => 'year'],
                    'product' => $stripePlan->id,
                ]);
                $extras['month'] = ['stripe_price_id' => $stripe_monthly_plan->id,];
                $extras['year'] = ['stripe_price_id' => $stripe_yearly_plan->id,];
                $plan['extra'] = $extras;
                $recurring['usage_type'] = 'metered';
            }
            $stripe_monthly_plan = $stripe->prices->create([
                'nickname' => $plan['plan_name'] . " Monthly",
                'unit_amount' => $plan['plan_monthly_price'] * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => 'month'] + $recurring,
                'product' => $stripePlan->id,
            ]);
            $stripe_yearly_plan = $stripe->prices->create([
                'nickname' => $plan['plan_name'] . " Yearly",
                'unit_amount' => $plan['plan_yearly_price'] * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => 'year'] + $recurring,
                'product' => $stripePlan->id,
            ]);
            $plan['stripe_plan_id'] = $stripePlan->id;
            $plan['stripe_monthly_price_id'] = $stripe_monthly_plan->id;
            $plan['stripe_yearly_price_id'] = $stripe_yearly_plan->id;
            SubscriptionPlan::create($plan);
            echo $plan['plan_name'] . " was created \n ";
        }

    }
}
