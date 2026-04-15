<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('subscription_plan_id');
            $table->integer('organization_id');
            $table->tinyInteger('status')->default('4')->comment('0 : expire | 1 : pending | 2 : active | 4 created');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('transaction_id')->nullable();
            $table->tinyInteger('transaction_status')->default('1')->comment('0 : fail | 1 : pending | 2 : success');
            $table->string('session_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('plan_price')->nullable();
            $table->string('plan_months')->nullable();
            $table->string('plan_stripe_id')->nullable();
            $table->string('plan_stripe_price_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_subscriptions');
    }
}
