<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits';

    protected $fillable = ['user_id', 'plan_id', 'search_credits', 'expiry_date'];

    /**
     * Get the user that owns the credits.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
