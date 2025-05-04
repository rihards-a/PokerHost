<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'player_id',
        'amount',
        'type',
    ];

    public function handPlayer()
    {
        return $this->belongsTo(Player::class);
    }
}
