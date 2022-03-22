<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Carbon\Carbon;

class CreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('credits')->insert([
            'user_id' => 1,
            'plan_id' => 1,
            'search_credits' => 10000,
            'expiry_date' => Carbon::parse('2022-10-01'),
            'created_at' => Date::now(),
            'updated_at' => Date::now()
        ]);
    }
}
