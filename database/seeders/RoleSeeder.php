<?php

namespace Database\Seeders;

use App\Models\PaymentStyle;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Role::truncate();
        $items = [['id'   => 1,
            'name' => 'Admin'],
            ['id'   => 2,
                'name' => 'Organization'],
            ['id'   => 3,
                'name' => 'Instructor'],
            ['id'   => 4,
                'name' => 'Student'],

        ];
        foreach ( $items as $item ) {
            Role::create( $item );
        }
    }
}
