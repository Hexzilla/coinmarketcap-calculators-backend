<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

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
            'name' => 'Standard',
            'users' => 1,
            'price' => 55,
            'price_yearly' => 35,
            'created_at' => Date::now(),
            'updated_at' => Date::now()
        ]);
        DB::table('plans')->insert([
            'name' => 'Premium',
            'users' => 3,
            'price' => 45,
            'price_yearly' => 69,
            'created_at' => Date::now(),
            'updated_at' => Date::now()
        ]);
        DB::table('plans')->insert([
            'name' => 'Enterprise',
            'users' => 10,
            'price' => 65,
            'price_yearly' => 99,
            'created_at' => Date::now(),
            'updated_at' => Date::now()
        ]);
    }
}
