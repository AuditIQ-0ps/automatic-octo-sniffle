<?php

namespace Database\Seeders;

use App\Models\PaymentStyle;
use Illuminate\Database\Seeder;

class PaymentStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        PaymentStyle::truncate();
        $items = [['id' => 1,
            'style' => 'Yearly'],
            ['id' => 2,
                'style' => 'Monthly'],
        ];
        foreach ($items as $item) {
            PaymentStyle::create($item);
        }
    }
}
