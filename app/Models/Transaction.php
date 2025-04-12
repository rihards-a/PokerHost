<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'hand_player_id',
        'amount',
        'type',
    ];

    public function handPlayer()
    {
        return $this->belongsTo(HandPlayer::class);
    }
}
