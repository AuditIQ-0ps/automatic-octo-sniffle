<?php

namespace Database\Seeders;

use App\Models\QuestionType;
use Illuminate\Database\Seeder;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $items = [['id'   => 1,
            'type' => 'MCQ'],
            ['id'   => 2,
                'type' => 'Short Answer'],
        ];
        foreach ( $items as $item ) {
            QuestionType::create( $item );
        }
    }
}
