<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('trail')->default(0)->comment("Trails in  days");
            $table->dropColumn('plan_months');
            $table->dropColumn('plan_price');
            $table->dropColumn('stripe_recurring_plan_id');
            $table->dropColumn('stripe_monthly_plan_id');


            $table->double('plan_monthly_price');
            $table->double('plan_yearly_price');

            $table->string('type')->default('Par User')->comment('Flat or Par User');

            $table->string('stripe_monthly_price_id')->nullable();
            $table->string('stripe_yearly_price_id')->nullable();
            $table->integer('meeting_time')->comment("group meetings for up minute (-1 for unlimited)");
            $table->integer('min_participants')->comment("min participants per meeting ")->default(0);
            $table->integer('max_participants')->comment("min participants per meeting (-1 for unlimited)");
            $table->integer('storage')->comment("cloud storage per user (In GB)");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            //
        });
    }
}
