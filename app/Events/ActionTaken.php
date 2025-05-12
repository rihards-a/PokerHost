<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionTaken implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $action;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $action)
    {
        $this->tableId = $tableId;
        $this->action = $action;
    }

    public function broadcastWith(): array
    {
        return [
            'seatId' => $this->action->seat_id,
            'action' => $this->action->action_type,
            'amount' => $this->action->amount,
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

    public function broadcastAs(): string
    {
        return 'action.taken';
    }
}
