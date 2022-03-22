<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_refunds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('subscription_tbl_id');
            $table->string('refund_id');
            $table->string('amount');
            $table->string('currency');
            $table->string('status');
            $table->timestamps();
            $table->foreign('subscription_tbl_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_refunds');
    }
};
