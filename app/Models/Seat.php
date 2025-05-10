<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'table_id',
        'player_id',
        'position',
    ];

    public function isTaken()
    {   
        if ($this->player) {
            return $this->player->user_id || $this->player->guest_session;
        }
        return false;
    }

    /**
     * Get the next active seat in the table
     * @return Seat|null
     */
    public function getNextActive()
    {
    // 1) Try to find the very next seat with a higher position
    $next = Seat::where('table_id', $this->table_id)
        ->where('position', '>', $this->position)
        ->whereHas('player', fn($q) => $q->where('active', true))
        ->orderBy('position', 'asc')
        ->first();
    
    if ($next) {
        return $next;
    }
    
    // 2) Wrap around: pick the lowest active seat
    return Seat::where('table_id', $this->table_id)
        ->where('position', '<', $this->position) // Ensure we only get positions lower than current
        ->whereHas('player', fn($q) => $q->where('active', true))
        ->orderBy('position', 'asc')
        ->first();
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
