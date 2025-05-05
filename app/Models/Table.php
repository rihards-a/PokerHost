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
        'host_id',
    ];

    /**
     * Get all open tables
     */
    public static function getOpenTables()
    {
        return self::where('status', 'open')
            ->with(['host', 'seats'])
            ->get();
    }
    
    /**
     * Check if table is full
     */
    public function isFull()
    {
        return $this->occupiedSeatsCount() >= $this->max_seats;
    }

    /**
     * Count the number of occupied seats
     */
    public function occupiedSeats()
    {
        return $this->seats()
        ->whereHas('player', function($q) {
            $q->whereNotNull('user_id')
              ->orWhereNotNull('guest_session');
        });
    }

    public function host()
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
