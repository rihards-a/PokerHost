<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandPlayer extends Model
{
    protected $fillable = [
        'hand_id',
        'seat_id',
        'status',
    ];

    public function hand()
    {
        return $this->belongsTo(Hand::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
