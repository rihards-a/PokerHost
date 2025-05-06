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
    public function nextActive() {
        $nextSeat = Seat::where('table_id', $this->table_id)
            ->where('position', '>', $this->position)
            ->whereHas('player', function($query) {
                $query->where('status', 'active');
            })
            ->first();
        // Edge case: it's the last seat in the table
        if (!$nextSeat) {
            $nextSeat = Seat::where('table_id', $this->table_id)
                ->where('position', '<=', $this->position)
                ->whereHas('player', function($query) {
                    $query->where('status', 'active');
                })
                ->first();
        }
        return $nextSeat;
    }

    /**
     * Get the previous active seat in the table
     * @return Seat|null
     */
    public function previousActive() {
        $previousSeat = Seat::where('table_id', $this->table_id)
            ->where('position', '<', $this->position)
            ->whereHas('player', function($query) {
                $query->where('status', 'active');
            })
            ->first();
        // Edge case: it's the first seat in the table
        if (!$previousSeat) {
            $previousSeat = Seat::where('table_id', $this->table_id)
                ->where('position', '>=', $this->position)
                ->whereHas('player', function($query) {
                    $query->where('status', 'active');
                })
                ->first();
        }
        return $previousSeat;
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
