<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->text('name')->nullable();
            $table->bigInteger('phone')->nullable();
            $table->bigInteger('mobile')->nullable();
            $table->text('description')->nullable();
            $table->integer('price')->nullable();
            $table->integer('no_of_instructor')->nullable();
            $table->string('withdrawal_date')->nullable();
            $table->integer('payment_style_id')->nullable();
            $table->integer('no_of_student')->nullable();
            $table->string('build_year')->nullable();
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
        Schema::dropIfExists('organizations');
    }
}
