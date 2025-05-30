<?php

namespace App\Events;

use App\Models\Seat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableSeatUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $seat;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $seat)
    {
        $this->tableId = $tableId;
        
        // Only send necessary seat data, not the entire model
        $this->seat = [
            'id' => $seat->id,
            'position' => $seat->position,
            'isOccupied' => !!($seat->isTaken()),
            'userId' => $seat->player->user_id,
            'userName' => $seat->player->user ? $seat->player->user->name : $seat->player->guest_name,
            'isGuest' => !$seat->player->user_id && !!$seat->player->guest_session,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('table.' . $this->tableId),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'seat.updated';
    }
}
