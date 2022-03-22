<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $guarded = [];

    public function plan()
    {
        return $this->hasOne(Plan::class,'stripe_plan','stripe_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}