<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'round_id',
        'hand_player_id',
        'action_type',
        'amount',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function handPlayer()
    {
        return $this->belongsTo(HandPlayer::class);
    }
}
