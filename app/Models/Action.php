<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'round_id',
        'seat_id',
        'action_type',
        'amount',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
}
