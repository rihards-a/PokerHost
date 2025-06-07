<?php

namespace App\Services;

use App\Events\TableStateUpdated;
use App\Models\Hand;
use App\Models\Table;
use App\Models\Seat;
use App\Models\SeatHand;
use App\Models\Action;
use Illuminate\Support\Arr;

class PokerStateService
{
    protected $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Get the complete state of a poker table
     *
     * @param Table $table
     * @return array
     */
    public function getTableState(Table $table): array
    {
        $currentHand = $this->getCurrentHand($table->id);
        $handId = $currentHand?->id;
        
        return [
            'table' =>              $this->getTableInfo($table),
            'hand' =>               $currentHand ? $this->getHandInfo($currentHand) : null,
            'round' =>              $currentHand ? $this->getRoundType($currentHand) : null,
            'seats' =>              $this->getSeatsInfo($table->id, $handId),
            'community_cards' =>    $currentHand ? $this->getCommunityCards($handId) : null,
            'pot' =>                $currentHand ? $this->getPotInfo($handId) : null,
            'last_action' =>        $currentHand ? $this->positionService->getLastAction($currentHand) : null,
            'current_seat' =>       $currentHand ? $this->positionService->getCurrentSeat($currentHand) : null,
        ];
    }
    
    /**
     * Get the current hand for a table
     *
     * @param int $tableId
     * @return \App\Models\Hand|null
     */
    private function getCurrentHand(int $tableId)
    {
        return Hand::where('table_id', $tableId)
            ->where('is_complete', false)
            ->latest()
            ->first();
    }
    
    /**
     * Get general table information
     *
     * @param \App\Models\Table $table
     * @return array
     */
    private function getTableInfo(Table $table): array
    {
        return [
            'id' => $table->id,
            'name' => $table->name,
            'max_seats' => $table->max_seats,
            'status' => $table->status,
            'host_id' => $table->host_id,
            'game_type' => $table->game_type,
        ];
    }
    
    /**
     * Get hand-specific information
     *
     * @param \App\Models\Hand $hand
     * @return array
     */
    private function getHandInfo(Hand $hand): array
    {
        return [
            'id' => $hand->id,
            'dealer_id' => $hand->dealer_id,
            'small_blind_id' => $hand->small_blind_id,
            'big_blind_id' => $hand->big_blind_id,
            'is_complete' => $hand->is_complete,
            'pot_size' => $hand->pot_size,
        ];
    }

    private function getRoundType(Hand $hand): array
    {
        $round = $hand->rounds()->where('is_complete', false)->latest()->first();
        return [
            'type' => $round->type,
        ];
    }

    /**
     * Get information about all seats at the table
     *
     * @param int $tableId
     * @param int|null $handId
     * @return array
     */
    private function getSeatsInfo(int $tableId, ?int $handId): array
    {
        $hand = Hand::find($handId);
        $to_act = $handId ? $this->positionService->getCurrentSeat($hand)->id : null;
        $seats = Seat::where('table_id', $tableId)->with('player')->get();
        $seatData = [];
        
        foreach ($seats as $seat) {
            $taken = $seat->isTaken();
            $guest = $taken ? !!$seat->player->guest_session : false;

            $seatData[$seat->id] = [
                'id' => $seat->id,
                'position' => $seat->position,
                'occupied' => $taken,
            ];
            if ($taken) {
                $player = $seat->player;
                $seatData[$seat->id] += [
                    'player' => [
                        'id' => $seat->player->id,
                        'name' => $guest ? $player->guest_name : $player->user->name,
                    ],
                    'stack' => $player->balance,
                    'status' => $this->getPlayerStatus($handId, $seat->id), // 'active', 'folded', 'all-in', etc.
                    'type' => $this->getCurrentBet($handId, $seat->id)[1],
                    'amount' => $this->getCurrentBet($handId, $seat->id)[0], // get the current total bet amount in the current round
                    'is_turn' => $handId && $seat->id === $to_act,
                ];
            }
        }
        
        return $seatData;
    }
    
    
    /**
     * Get the current bet amount and last action type for a player in the current hand.
     *
     * @param int|null $handId
     * @param int $seatId
     * @return array [int $currentBet, string|null $latestActionType]
     */
    private function getCurrentBet(?int $handId, int $seatId): array
    {
        if (!$handId) return [0, null];

        $hand = Hand::with('rounds')->find($handId);
        if (!$hand) return [0, null];

        $round = $hand->rounds()->where('is_complete', false)->latest()->first();
        if (!$round) return [0, null];

        $currentBet = Action::where('seat_id', $seatId)
            ->where('round_id', $round->id)
            ->whereIn('action_type', ['allin', 'bet', 'raise', 'call'])
            ->sum('amount');

        $latestActionType = Action::where('seat_id', $seatId)
            ->where('round_id', $round->id)
            ->latest()
            ->value('action_type');

        return [$currentBet, $latestActionType];
    }
    
    /**
     * Get the player's status in the current hand
     *
     * @param int|null $handId
     * @param int $seatId
     * @return string|null
     */
    private function getPlayerStatus(?int $handId, int $seatId): ?string
    {
        if (!$handId) return null;
        
        $seatHand = SeatHand::where('hand_id', $handId)
            ->where('seat_id', $seatId)
            ->first();
        
        return $seatHand?->status;
    }
    
    /**
     * Get community cards for the current hand
     *
     * @param int $handId
     * @return array
     */
    private function getCommunityCards(int $handId): array
    {
        $hand = Hand::with('rounds')->findOrFail($handId);
        $handCards = json_decode($hand->community_cards);
        $round = $hand->rounds()->where('is_complete', false)->latest()->first();
        
        $cards = [];
        
        if (in_array($round->type, ['flop', 'turn', 'river'])) {
            $cards = array_slice($handCards, 0, 3);
        } else return [];
        
        if (in_array($round->type, ['turn', 'river'])) {
            $cards[] = $handCards[3];
        }
        
        if (in_array($round->type, ['river'])) {
            $cards[] = $handCards[4];
        }
        
        return array_filter($cards);
    }
    
    /**
     * Get information about the pot(s)
     *
     * @param int $handId
     * @return int
     */
    private function getPotInfo(int $handId): int
    {                
        // In a real implementation, you'd calculate the main pot and side pots
        // For now, just sum up all bets
        $totalBets = Action::whereHas('round', function ($query) use ($handId) {
            $query->where('hand_id', $handId);
        })->whereIn('action_type', ['bet', 'raise', 'call', 'allin'])
          ->sum('amount');

        return $totalBets;
    }
}
