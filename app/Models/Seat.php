<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'table_id',
        'player_id',
        'position',
        'is_dealer',
    ];

    public function isTaken()
    {   
        if ($this->player) {
            return $this->player->user_id || $this->player->guest_session;
        }
        return false;
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function seatHand()
    {
        return $this->hasMany(SeatHand::class);
    }

    public function action()
    {
        return $this->hasMany(Action::class);
    }
}
