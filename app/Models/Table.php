<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name', 
        'max_seats', 
        'status',
        'game_type',
        'host',
    ];

    /**
     * Get all open tables
     */
    public static function getOpenTables()
    {
        return self::where('status', 'open')
            ->with(['hostUser', 'seats'])
            ->get();
    }

    /**
     * Count the number of occupied seats
     */
    public function occupiedSeatsCount()
    {
        return $this->seats()->whereNotNull('user_id')->count();
    }

    /**
     * Check if table is full
     */
    public function isFull()
    {
        return $this->occupiedSeatsCount() >= $this->max_seats;
    }

    public function hostUser()
    {
        return $this->belongsTo(User::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function hands()
    {
        return $this->hasMany(Hand::class);
    }
}
