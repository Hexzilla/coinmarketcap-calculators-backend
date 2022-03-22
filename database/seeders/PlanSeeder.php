<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('plans')->insert([
            'plan_name' => 'TestPlan',
            'plan_price' => 249,
            'plan_credits' => 10000,
            'created_at' => Date::now(),
            'updated_at' => Date::now()
        ]);
    }
}
