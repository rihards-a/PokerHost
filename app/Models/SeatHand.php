<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatHand extends Model
{
    protected $fillable = [
        'status',
        'hand_id',
        'seat_id',
        'card1',
        'card2',
    ];

    public function hand()
    {
        return $this->belongsTo(Hand::class);
    }
    
    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }
}
