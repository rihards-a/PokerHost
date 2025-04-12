<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hand extends Model
{
    protected $fillable = [
        'table_id',
        'dealer_seat_id',
        'community_cards',
    ];

    public function table()
    {
        return $this->belongsTo(PokerTable::class);
    }

    public function dealerSeat()
    {
        return $this->belongsTo(Seat::class, 'dealer_seat_id');
    }

    public function players()
    {
        return $this->hasMany(HandPlayer::class);
    }

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}
