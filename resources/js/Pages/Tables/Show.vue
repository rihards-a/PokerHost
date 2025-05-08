<template>
  <div class="table-container">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
      <!-- Table header -->
      <div class="bg-white shadow-md rounded-lg mb-6 p-6">
        <div class="flex justify-between items-center flex-wrap gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ table.name }}</h1>
            <div class="text-sm text-gray-500 mt-1">
              <span class="font-medium">Game:</span> {{ formatGameType(table.gameType) }}
              <span class="mx-2">•</span>
              <span class="font-medium">Host:</span> {{ table.hostName }}
              <span class="mx-2">•</span>
              <span class="font-medium">Created:</span> {{ table.created }}
            </div>
          </div>
          
          <div class="flex gap-3">
            <span 
              :class="[
                'px-3 py-1 rounded-full text-sm font-medium',
                tableStatus === 'open' ? 'bg-green-100 text-green-800' : 
                  isPlaying ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'
              ]"
            >
              {{ tableStatus === 'open' ? 'Open' : (isPlaying ? 'Playing' : 'Closed') }}
            </span>
            
            <Link 
              v-if="!isAtTable"
              href="/" 
              class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition"
            >
              <span>Back to Tables</span>
            </Link>
            
            <button 
              v-if="isHost && !isPlaying" 
              @click="toggleTableStatus"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 transition"
            >
              {{ tableStatus === 'open' ? 'Close Table' : 'Open Table' }}
            </button>
            
            <button 
              v-if="isHost && tableStatus !== 'open' && !isPlaying && hasEnoughPlayers" 
              @click="startGame"
              class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-500 transition"
            >
              Start Game
            </button>
          </div>
        </div>
      </div>
      
      <!-- Game info and pot -->
      <div v-if="isPlaying" class="bg-white shadow-md rounded-lg mb-6 p-4">
        <div class="flex justify-between items-center">
          <div>
            <div class="text-lg font-bold">Hand #{{ currentHandId || '--' }}</div>
            <div class="text-sm text-gray-600 mt-1">
              <span v-if="dealerSeat">Dealer: Seat {{ dealerSeat.position }}</span>
              <span v-if="dealerSeat && smallBlindSeat" class="mx-2">•</span>
              <span v-if="smallBlindSeat">Small Blind: Seat {{ smallBlindSeat.position }}</span>
              <span v-if="smallBlindSeat && bigBlindSeat" class="mx-2">•</span>
              <span v-if="bigBlindSeat">Big Blind: Seat {{ bigBlindSeat.position }}</span>
            </div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold">${{ currentPot }}</div>
            <div class="text-sm text-gray-600">Current Pot</div>
          </div>
          <div class="text-right">
            <div v-if="activeSeat" class="text-lg font-semibold text-blue-600">
              {{ isUserTurn ? "Your Turn" : `${activeSeat.userName}'s Turn` }}
            </div>
            <div class="text-sm text-gray-600">
              Round: {{ formatRound(currentRound) }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Community cards -->
      <div v-if="isPlaying" class="bg-white shadow-md rounded-lg mb-6 p-4 text-center">
        <div class="mb-2 text-sm text-gray-600">Community Cards</div>
        <div class="flex justify-center gap-2">
          <div 
            v-for="(card, index) in communityCards" 
            :key="index" 
            class="card-container"
          >
            <div class="w-12 h-16 bg-white rounded-md border border-gray-300 flex items-center justify-center shadow-sm">
              <span :class="['font-bold', card.suit === '♥' || card.suit === '♦' ? 'text-red-600' : 'text-gray-900']">
                {{ card.value }}{{ card.suit }}
              </span>
            </div>
          </div>
          <div 
            v-for="n in (5 - communityCards.length)" 
            :key="`empty-${n}`" 
            class="w-12 h-16 bg-gray-100 rounded-md border border-gray-300 flex items-center justify-center shadow-sm"
          >
            <span class="text-gray-400">?</span>
          </div>
        </div>
      </div>
      
      <!-- Poker table visualization -->
      <div class="poker-table-container bg-white shadow-md rounded-lg overflow-hidden">
        <div class="relative bg-green-700 rounded-full mx-auto my-12 flex justify-center items-center" style="width: 80%; height: 400px;">
          <!-- Table felt -->
          <div class="absolute inset-0 rounded-full bg-green-800 m-10"></div>
          
          <!-- Center info -->
          <div class="relative z-10 text-white text-center">
            <h2 class="font-bold text-xl">{{ table.name }}</h2>
            <p v-if="!isPlaying">{{ formatGameType(table.gameType) }}</p>
            <div v-else class="text-2xl font-bold mt-2">${{ currentPot }}</div>
          </div>
          
          <!-- Seats around the table -->
          <div v-for="seat in displaySeats" :key="seat.id" class="absolute z-20" :style="positionSeat(seat.position, displaySeats.length)">
            <div class="seat-container">
              <div 
                :class="[
                  'relative w-24 h-24 rounded-full flex items-center justify-center flex-col',
                  getSeatClass(seat)
                ]"
              >
                <!-- Dealer button -->
                <div 
                  v-if="isPlaying && dealerSeat && dealerSeat.id === seat.id" 
                  class="absolute -top-3 left-0 w-6 h-6 bg-white text-gray-800 rounded-full flex items-center justify-center text-xs font-bold border-2 border-gray-800"
                >
                  D
                </div>
                
                <!-- Player cards -->
                <div v-if="isPlaying && seat.isOccupied && seat.cards && seat.cards.length > 0" class="absolute -bottom-6 flex gap-1">
                  <div 
                    v-for="(card, index) in seat.cards" 
                    :key="`card-${index}`" 
                    class="w-8 h-12 bg-white rounded-sm border border-gray-300 flex items-center justify-center shadow-sm transform rotate-3"
                  >
                    <span 
                      v-if="isCurrentUser(seat) || seat.showCards" 
                      :class="['text-xs font-bold', card.suit === '♥' || card.suit === '♦' ? 'text-red-600' : 'text-gray-900']"
                    >
                      {{ card.value }}{{ card.suit }}
                    </span>
                    <span v-else class="text-gray-400 text-xs">?</span>
                  </div>
                </div>
                
                <template v-if="seat.isOccupied">
                  <span class="font-medium text-sm">{{ seat.userName }}</span>
                  <span class="text-xs">
                    {{ isCurrentUser(seat) ? '(You)' : '' }}
                  </span>
                  <div v-if="isPlaying" class="mt-1 text-xs">
                    ${{ seat.chips || 0 }}
                  </div>
                  <div v-if="isPlaying && seat.currentBet" class="absolute -top-6 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-md text-xs font-medium">
                    Bet: ${{ seat.currentBet }}
                  </div>
                  <div 
                    v-if="activeSeat && activeSeat.id === seat.id" 
                    class="absolute -right-1 top-0 w-4 h-4 bg-blue-500 rounded-full animate-pulse"
                  ></div>
                </template>
                <template v-else>
                  <span>Seat {{ seat.position }}</span>
                  <button 
                    v-if="canJoinSeat && tableStatus === 'open'"
                    @click="joinSeat(seat.id)"
                    class="mt-2 text-xs bg-blue-600 text-white py-1 px-2 rounded hover:bg-blue-500"
                  >
                    Join
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Hand result display -->
      <div v-if="winnerInfo && winnerInfo.length > 0" class="mt-4 bg-white shadow-md rounded-lg p-4 mb-6">
        <h3 class="text-lg font-bold mb-2">Hand Result</h3>
        <div v-for="(winner, index) in winnerInfo" :key="`winner-${index}`" class="mb-2">
          <div class="flex items-center">
            <div class="text-lg font-medium">{{ winner.userName }}</div>
            <div class="ml-2 text-sm text-gray-600">
              won ${{ winner.amount }} with {{ winner.handName }}
            </div>
          </div>
          <div class="flex gap-1 mt-1">
            <div 
              v-for="(card, cardIndex) in winner.cards" 
              :key="`winner-card-${cardIndex}`" 
              class="w-8 h-12 bg-white rounded-sm border border-gray-300 flex items-center justify-center shadow-sm"
            >
              <span :class="['text-xs font-bold', card.suit === '♥' || card.suit === '♦' ? 'text-red-600' : 'text-gray-900']">
                {{ card.value }}{{ card.suit }}
              </span>
            </div>
          </div>
        </div>
        <button 
          v-if="isHost" 
          @click="startNewHand"
          class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 transition"
        >
          Deal Next Hand
        </button>
      </div>
      
      <!-- Player actions -->
      <div v-if="isPlaying && isUserTurn" class="mt-4 bg-white shadow-md rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold">Your Turn</h3>
            <div class="text-sm text-gray-600">Current bet: ${{ currentBet }}</div>
          </div>
          <div class="flex gap-2">
            <button 
              @click="playerAction('fold')"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500 transition"
            >
              Fold
            </button>
            <button 
              v-if="canCheck"
              @click="playerAction('check')"
              class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-500 transition"
            >
              Check
            </button>
            <button 
              v-else
              @click="playerAction('call')"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 transition"
            >
              Call ${{ callAmount }}
            </button>
            <button 
              @click="openBetDialog"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-500 transition"
            >
              {{ currentBet > 0 ? 'Raise' : 'Bet' }}
            </button>
          </div>
        </div>
        
        <!-- Bet dialog -->
        <div v-if="showBetDialog" class="mt-4 p-4 border border-gray-300 rounded-lg">
          <h4 class="font-medium mb-2">{{ currentBet > 0 ? 'Raise to' : 'Bet amount' }}</h4>
          <div class="flex items-center gap-2">
            <input 
              v-model.number="betAmount" 
              type="number" 
              :min="minBet" 
              :max="maxBet" 
              class="border border-gray-300 rounded px-3 py-2 w-32"
            />
            <div class="text-sm text-gray-600">
              Min: ${{ minBet }} | Max: ${{ maxBet }}
            </div>
            <button 
              @click="playerAction('bet')"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-500 transition ml-auto"
            >
              Confirm
            </button>
            <button 
              @click="showBetDialog = false"
              class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-400 transition"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
      
      <!-- Game log -->
      <div class="mt-4 bg-white shadow-md rounded-lg p-4 h-40 overflow-y-auto">
        <h3 class="text-lg font-bold mb-2">Game Log</h3>
        <div v-for="(log, index) in gameLogs" :key="`log-${index}`" class="text-sm mb-1">
          <span class="text-gray-500">{{ log.time }}:</span> 
          <span>{{ log.message }}</span>
        </div>
        <div v-if="gameLogs.length === 0" class="text-gray-500 text-sm">No activity yet</div>
      </div>
      
      <!-- Actions -->
      <div class="mt-6 text-center" v-if="userCurrentSeat && !isPlaying">
        <button 
          @click="leaveSeat"
          class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-500 transition"
        >
          Leave Table
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import Pusher from 'pusher-js';

export default {
  components: {
    Link
  },
  
  props: {
    table: Object,
    seats: Array,
    currentUserSeat: Object,
    isHost: Boolean
  },
  
  data() {
    return {
      displaySeats: [...this.seats],
      tableStatus: this.table.status,
      userCurrentSeat: this.currentUserSeat,
      isPlaying: false,
      pusher: null,
      channel: null,
      currentHandId: null,
      dealerSeat: null,
      smallBlindSeat: null, 
      bigBlindSeat: null,
      activeSeat: null,
      currentRound: null,
      communityCards: [],
      currentPot: 0,
      currentBet: 0,
      callAmount: 0,
      winnerInfo: null,
      showBetDialog: false,
      betAmount: 0,
      minBet: 2,
      maxBet: 100,
      gameLogs: []
    };
  },
  
  computed: {
    isAtTable() {
      return !!this.userCurrentSeat;
    },
    
    canJoinSeat() {
      return !this.userCurrentSeat;
    },
    
    hasEnoughPlayers() {
      // Require at least 2 players to start
      return this.displaySeats.filter(seat => seat.isOccupied).length >= 2;
    },
    
    isUserTurn() {
      return this.activeSeat && this.userCurrentSeat && this.activeSeat.id === this.userCurrentSeat.id;
    },
    
    canCheck() {
      if (!this.userCurrentSeat) return false;
      
      const userSeat = this.displaySeats.find(s => s.id === this.userCurrentSeat.id);
      const userCurrentBet = userSeat && userSeat.currentBet ? userSeat.currentBet : 0;
      
      return userCurrentBet >= this.currentBet;
    }
  },
  
  created() {
    this.connectToPusher();
  },
  
  beforeUnmount() {
    this.disconnectFromPusher();
  },
  
  methods: {
    connectToPusher() {
      // Initialize Pusher
      this.pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        encrypted: true
      });
      
      // Subscribe to the table channel
      this.channel = this.pusher.subscribe('table.' + this.table.id);
      this.secret = this.pusher.subscribe('table.' + this.table.id + '.seat.' + this.userCurrentSeat.id); // private channel
      
      // Bind to events
      this.channel.bind('seat.updated', (data) => {
        this.handleSeatUpdate(data.seat);
      });
      
      this.channel.bind('status.updated', (data) => {
        this.handleStatusUpdate(data.status);
      });
      
      this.channel.bind('turn.changed', (data) => {
        this.handleTurnChange(data.seatId);
      });
      
      this.channel.bind('round.advanced', (data) => {
        this.handleRoundAdvance(data.roundType, data.cards);
      });
      
      this.channel.bind('hand.finished', (data) => {
        this.handleHandFinished(data.handId, data.winners);
      });
      
      this.channel.bind('hand.started', (data) => {
        this.handleHandStarted(data.handId, data.data);
      });

      this.secret.bind('cards.dealt', (data) => {
        this.handleCardsDealt(data.seatId, data.cards);
      });
      
      // Add listener for the new cards.dealt event
      //this.secret.bind('cards.dealt', (data) => {
      //  this.handleCardsDealt(data.seatId, data.cards);
      //});
    },
    
    disconnectFromPusher() {
      if (this.channel) {
        this.channel.unbind_all();
        this.pusher.unsubscribe('table.' + this.table.id);
      }
      
      if (this.pusher) {
        this.pusher.disconnect();
      }
    },
    
    handleSeatUpdate(updatedSeat) {
      // Find and update the seat in our local state
      const index = this.displaySeats.findIndex(s => s.id === updatedSeat.id);
      if (index !== -1) {
        // Preserve cards if they exist
        const existingCards = this.displaySeats[index].cards || [];
        this.displaySeats[index] = { ...updatedSeat, cards: existingCards };
      }
      
      // Update user's current seat if relevant
      if (this.userCurrentSeat && this.userCurrentSeat.id === updatedSeat.id) {
        if (!updatedSeat.isOccupied) {
          this.userCurrentSeat = null;
        } else {
          this.userCurrentSeat = updatedSeat;
        }
      }
      
      // Update dealer, small blind, big blind if needed
      if (this.dealerSeat && this.dealerSeat.id === updatedSeat.id) {
        this.dealerSeat = updatedSeat;
      }
      if (this.smallBlindSeat && this.smallBlindSeat.id === updatedSeat.id) {
        this.smallBlindSeat = updatedSeat;
      }
      if (this.bigBlindSeat && this.bigBlindSeat.id === updatedSeat.id) {
        this.bigBlindSeat = updatedSeat;
      }
      
      // Add to log if relevant
      if (updatedSeat.isOccupied) {
        if (updatedSeat.currentBet) {
          this.addToLog(`${updatedSeat.userName} bet $${updatedSeat.currentBet}`);
        }
      }
    },
    
    handleStatusUpdate(status) {
      this.tableStatus = status;
      this.addToLog(`Table status changed to ${status}`);
    },
    
    handleTurnChange(seatId) {
      const seat = this.displaySeats.find(s => s.id === seatId);
      this.activeSeat = seat;
      
      if (seat) {
        this.addToLog(`It's ${seat.userName}'s turn`);
      }
    },
    
    handleRoundAdvance(roundType, cards) {
      this.currentRound = roundType;
      
      if (cards) {
        this.communityCards = [...cards];
      }
      
      this.addToLog(`Round advanced to ${this.formatRound(roundType)}`);
      
      // Reset current bet for new betting round
      this.currentBet = 0;
      this.displaySeats.forEach(seat => {
        if (seat.isOccupied) {
          seat.currentBet = 0;
        }
      });
    },
    
    handleHandFinished(handId, winners) {
      this.winnerInfo = winners;
      this.isPlaying = false;
      
      winners.forEach(winner => {
        this.addToLog(`${winner.userName} won $${winner.amount} with ${winner.handName}`);
      });
      
      // Update player cards to show all
      this.displaySeats.forEach(seat => {
        if (seat.isOccupied && seat.cards) {
          seat.showCards = true;
        }
      });
    },
    
    handleHandStarted(handId, data) {
      this.currentHandId = handId;
      this.isPlaying = true;
      this.winnerInfo = null;
      this.communityCards = [];
      this.currentPot = 0;
      this.currentBet = 0;
      
      // Reset all player cards
      this.displaySeats.forEach(seat => {
        if (seat.isOccupied) {
          seat.cards = [];
          seat.currentBet = 0;
          seat.showCards = false;
        }
      });
      
      // Set dealer and blinds
      const dealerSeat = this.displaySeats.find(s => s.id === data.dealer);
      const smallBlindSeat = this.displaySeats.find(s => s.id === data.small_blind);
      const bigBlindSeat = this.displaySeats.find(s => s.id === data.big_blind);
      const nextToAct = this.displaySeats.find(s => s.id === data.next_to_act);
      
      this.dealerSeat = dealerSeat;
      this.smallBlindSeat = smallBlindSeat;
      this.bigBlindSeat = bigBlindSeat;
      this.activeSeat = nextToAct;
      this.currentRound = 'preflop';
      
      // Simulate giving cards to others (in a real app, these would just be face down)
      this.displaySeats.forEach(seat => {
        if (seat.isOccupied) {
          seat.cards = [{ value: '?', suit: '?' }, { value: '?', suit: '?' }];
        }
      });
      
      this.addToLog(`Hand #${handId} started`);
      if (dealerSeat) {
        this.addToLog(`${dealerSeat.userName} is the dealer`);
      }
    },
    
    // Handle the new cards.dealt event
    handleCardsDealt(seatId, cards) {
      // This event provides actual card values for a specific seat (should be the user's seat)
      const seatIndex = this.displaySeats.findIndex(s => s.id === seatId);
      
      if (seatIndex !== -1) {
        // Convert the cards format to match our UI format
        const formattedCards = [];
        
        if (cards.card1) {
          const value = cards.card1.charAt(0);
          const suitCode = cards.card1.charAt(1);
          const suit = this.convertSuitCode(suitCode);
          formattedCards.push({ value, suit });
        }
        
        if (cards.card2) {
          const value = cards.card2.charAt(0);
          const suitCode = cards.card2.charAt(1);
          const suit = this.convertSuitCode(suitCode);
          formattedCards.push({ value, suit });
        }
        
        // Update the seat with the actual cards
        this.displaySeats[seatIndex].cards = formattedCards;
        
        // If this is the current user's seat, log that they received cards
        if (this.userCurrentSeat && this.userCurrentSeat.id === seatId) {
          this.addToLog(`You received your cards: ${formattedCards.map(c => c.value + c.suit).join(', ')}`);
        }
      }
    },
    
    // Helper to convert suit code to actual suit symbol
    convertSuitCode(code) {
      const suitMap = {
        's': '♠',
        'h': '♥',
        'd': '♦',
        'c': '♣'
      };
      return suitMap[code.toLowerCase()] || code;
    },
    
    formatGameType(gameType) {
      if (gameType === 'TexasHoldem') return 'Texas Hold\'em';
      return gameType;
    },
    
    formatRound(round) {
      if (!round) return 'Waiting';
      
      const rounds = {
        'preflop': 'Pre-Flop',
        'flop': 'Flop',
        'turn': 'Turn',
        'river': 'River',
        'showdown': 'Showdown'
      };
      
      return rounds[round] || round;
    },
    
    positionSeat(position, totalSeats) {
      // Calculate position around the table
      const radius = 180; // Distance from center of table
      const angle = ((position - 1) / totalSeats) * 2 * Math.PI;
      const centerAdjustment = { x: 0, y: 20 }; // Adjust center point if needed
      
      const x = radius * Math.sin(angle) + centerAdjustment.x;
      const y = -radius * Math.cos(angle) + centerAdjustment.y;
      
      return {
        transform: `translate(${x}px, ${y}px)`,
      };
    },
    
    getSeatClass(seat) {
      if (seat.isOccupied) {
        if (this.isCurrentUser(seat)) {
          return 'bg-blue-500 text-white';
        }
        return 'bg-gray-300 text-gray-800';
      }
      return 'bg-gray-200 text-gray-600';
    },
    
    isCurrentUser(seat) {
      return this.userCurrentSeat && this.userCurrentSeat.id === seat.id;
    },
    
    joinSeat(seatId) {
      useForm({}).post(route('seats.join', seatId), {
        preserveScroll: true,
        onSuccess: () => {
          // Note: We don't need to update the UI here as we'll receive
          // the update via the Pusher broadcast
        }
      });
    },
    
    leaveSeat() {
      if (!this.userCurrentSeat) return;
      
      useForm({}).post(route('seats.leave', this.userCurrentSeat.id), {
        preserveScroll: true,
        onSuccess: () => {
          // Note: We don't need to update the UI here as we'll receive
          // the update via the Pusher broadcast
        }
      });
    },
    
    toggleTableStatus() {
      // Update to use the new route
      useForm({}).post(route('tables.toggle-status', this.table.id), {
        preserveScroll: true,
        onSuccess: () => {
          // Note: We don't need to update the UI here as we'll receive
          // the update via the Pusher broadcast
        }
      });
    },
    
    startGame() {
      useForm({}).post(route('tables.start-hand', this.table.id), {
        preserveScroll: true,
        onSuccess: () => {
          // Game will be started via Pusher events
        }
      });
    },
    
    startNewHand() {
      useForm({}).post(route('tables.start-hand', this.table.id), {
        preserveScroll: true,
        onSuccess: () => {
          // Hand will be started via Pusher events
        }
      });
    },
    
    playerAction(action, amount = null) {
      if (!this.isUserTurn) return;

      const data = { action };
      if (action === 'bet' && this.betAmount > 0) {
        data.amount = this.betAmount;
        this.showBetDialog = false;
      }
      if (action === 'call') {
        data.amount = this.callAmount;
      }

      useForm(data).post(route('tables.action.process', this.table.id), {
        preserveScroll: true,
        onSuccess: () => {
          // server will broadcast updates via Pusher
          this.betAmount = 0;
        }
      });
    },

    fold() {
      this.playerAction('fold');
    },

    check() {
      this.playerAction('check');
    },

    call() {
      this.playerAction('call');
    },

    openBetDialog() {
      this.showBetDialog = true;
      // set sensible defaults
      this.betAmount = this.minBet;
    },

    closeBetDialog() {
      this.showBetDialog = false;
      this.betAmount = 0;
    },

    submitBet() {
      if (this.betAmount < this.minBet || this.betAmount > this.maxBet) {
        return; // you could show validation here
      }
      this.playerAction('bet');
    },

    addToLog(message) {
      const timestamp = new Date().toLocaleTimeString();
      this.gameLogs.push({ time: timestamp, message });
      // keep the log array to a reasonable size
      if (this.gameLogs.length > 100) {
        this.gameLogs.shift();
      }
    }
  },
};
</script>