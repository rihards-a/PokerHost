<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'table_id',
        'player_id',
        'dealer_seat_id',
        'position',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class);
    }

    public function handsAsDealer()
    {
        return $this->hasMany(Hand::class, 'dealer_seat_id'); // the Seat
    }
    
    public function players()
    {
        return $this->hasMany(HandPlayer::class);
    }

}
