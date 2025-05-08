<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerTurnChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $seatId;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $seatId)
    {
        $this->tableId = $tableId;
        $this->seatId = $seatId;
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

    public function broadcastAs(): string
    {
        return 'turn.changed';
    }
}
