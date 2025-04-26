<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'table_id',
        'user_id',
        'guest_id',
        'guest_name',
        'guest_session',
        'dealer_seat_id',
        'position',
        'balance',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*public function handsAsDealer()
    {
        return $this->hasMany(Hand::class, 'dealer_seat_id'); // the Seat
    }*/
    
    public function players()
    {
        return $this->hasMany(HandPlayer::class);
    }

}
