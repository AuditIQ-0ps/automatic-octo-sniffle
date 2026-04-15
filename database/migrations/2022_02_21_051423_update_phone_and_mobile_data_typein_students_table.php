<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePhoneAndMobileDataTypeinStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('phone')->change();
            $table->string('mobile')->change();
        });
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('phone')->change();
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('phone')->change();
            $table->string('mobile')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
