<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'status',
        'balance',
        'guest_name',
        'guest_session',
        'user_id',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seat()
    {
        return $this->hasMany(Seat::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
