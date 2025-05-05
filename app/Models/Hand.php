<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hand extends Model
{
    protected $fillable = [
        'table_id',
        'dealer_id',
        'small_blind_id',
        'big_blind_id',
        'community_cards',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function seatHand()
    {
        return $this->hasMany(SeatHand::class);
    }

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}
