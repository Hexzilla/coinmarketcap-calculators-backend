<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeIdToPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo', function (Blueprint $table) {
            Schema::table('promo', function (Blueprint $table) {
                $table->string('stripe_id')->default('')->after('expiry_date');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo', function (Blueprint $table) {
            Schema::table('promo', function (Blueprint $table) {
                $table->string('stripe_id')->default('')->after('expiry_date');
            });
        });
    }
};
