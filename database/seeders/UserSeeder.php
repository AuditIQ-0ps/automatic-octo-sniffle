<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::truncate();
        DB::table('users')->insert([
            'first_name'=>"Admin",
            'last_name'=>"Admin",
            'email'=>env('ADMIN_USERNAME','admin@demo.com'),
            'password' => Hash::make('12345678'),
            'email_verified_at'=>  Carbon::now(),
            'role_id'=>1,
            'status'=>1
        ]);
    }
}
