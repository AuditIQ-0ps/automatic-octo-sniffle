<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUidInStreamUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stream_users', function (Blueprint $table) {
            $table->string('uid')->after('peer_id')->nullable();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->string('uid')->after('peer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stream_users', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

    }
}
