<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundAdvanced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tableId;
    public $roundType;
    public $cards;

    /**
     * Create a new event instance.
     */
    public function __construct($tableId, $roundType, $cards = null)
    {
        $this->tableId = $tableId;
        $this->roundType = $roundType;
        $this->cards = $cards;
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
        return 'round.advanced';
    }
}
