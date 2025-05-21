<template>
    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-4">Poker Table: {{ table.name }}</h1>
      
      <div class="poker-table-container p-4">
        <div class="mb-4 p-2 bg-gray-100 rounded">
          <p><strong>Game Type:</strong> {{ table.gameType }}</p>
          <p><strong>Host:</strong> {{ table.hostName }}</p>
          <p><strong>Status:</strong> {{ table.status }}</p>
          <p><strong>Created:</strong> {{ table.created }}</p>
        </div>
  
        <!-- Hand information -->
        <div v-if="currentHand" class="mb-4 p-2 bg-blue-100 rounded">
          <p><strong>Hand #{{ currentHand }}</strong></p>
          <p v-if="community.length > 0"><strong>Community Cards:</strong> {{ community.join(' ') }}</p>
          <p v-if="currentPot > 0"><strong>Pot:</strong> ${{ currentPot }}</p>
          <p v-if="currentRound"><strong>Round:</strong> {{ currentRound }}</p>
        </div>
  
        <!-- Your cards -->
        <div v-if="playerCards.length > 0" class="mb-4 p-2 bg-green-100 rounded">
          <h2 class="font-bold">Your Cards</h2>
          <p>{{ playerCards.join(' ') }}</p>
        </div>
  
        <!-- Table display -->
        <div class="poker-table relative bg-green-700 rounded-full h-64 w-full mb-6 flex items-center justify-center">
          <div v-if="currentDealer" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white font-bold bg-blue-800 p-1 rounded">
            Dealer
          </div>
          
          <!-- Seats around the table -->
          <div 
            v-for="seat in seats" 
            :key="seat.id"
            :class="[
              'seat absolute w-16 h-16 rounded-full flex items-center justify-center',
              seat.isOccupied ? 'bg-gray-300' : 'bg-gray-100',
              seat.id === currentTurnSeatId ? 'ring-4 ring-yellow-400' : '',
            ]"
            :style="getSeatPositionStyle(seat.position)"
          >
            <div v-if="seat.isOccupied" class="text-center">
              <p class="text-sm font-bold">{{ seat.userName }}</p>
              <p v-if="seatActions[seat.id]" class="text-xs">
                {{ seatActions[seat.id].type }} ${{ seatActions[seat.id].amount }}
              </p>
            </div>
            <button 
              v-else-if="!userCurrentSeat && !seat.isOccupied" 
              @click="joinSeat(seat.id)"
              class="text-xs bg-blue-500 text-white p-1 rounded hover:bg-blue-600"
            >
              Join
            </button>
          </div>
        </div>
  
        <!-- Table controls -->
        <div class="mb-4 flex space-x-2">
          <button 
            v-if="userCurrentSeat" 
            @click="leaveSeat()"
            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600"
          >
            Leave Seat
          </button>
          
          <button 
            v-if="isHost && table.status === 'closed' && hasEnoughPlayers"
            @click="startHand()"
            class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600"
          >
            Start Hand
          </button>
  
          <button 
              v-if="isHost" 
              @click="toggleTableStatus"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 transition"
            >
              {{ table.status === 'open' ? 'Close Table' : 'Open Table' }}
            </button>
        </div>
  
        <!-- Action buttons -->
        <div v-if="isPlayerTurn && table.status === 'closed'" class="mb-4 p-2 bg-yellow-100 rounded">
          <h2 class="font-bold mb-2">Your Turn</h2>
          
          <!-- Standard action buttons -->
          <div class="flex space-x-2 mb-3">
            <button
              @click="takeAction('fold')"
              class="px-3 py-1 rounded"
              :class="availableActions.includes('fold') ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
              :disabled="!availableActions.includes('fold')"
            >
              Fold
            </button>
            
            <button
              @click="takeAction('check')"
              class="px-3 py-1 rounded"
              :class="availableActions.includes('check') ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
              :disabled="!availableActions.includes('check')"
            >
              Check
            </button>
            
            <button
              @click="takeAction('call')"
              class="px-3 py-1 rounded"
              :class="availableActions.includes('call') ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
              :disabled="!availableActions.includes('call')"
            >
              Call
            </button>
            
            <button
              @click="takeAction('allin')"
              class="px-3 py-1 rounded"
              :class="availableActions.includes('allin') ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
              :disabled="!availableActions.includes('allin')"
            >
              All In
            </button>
          </div>
  
          <!-- Bet/Raise controls -->
          <div v-if="availableActions.includes('bet') || availableActions.includes('raise')" class="flex items-center space-x-3">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ availableActions.includes('bet') ? 'Bet Amount' : 'Raise Amount' }}
              </label>
              
              <!-- Slider for amount -->
              <input 
                type="range" 
                v-model.number="betAmount" 
                :min="minBetAmount" 
                :max="currentPlayer?.balance || 0" 
                class="w-full"
              />
              
              <!-- Manual input for amount -->
              <div class="flex mt-2 items-center">
                <input 
                  type="number" 
                  v-model.number="betAmount" 
                  :min="minBetAmount" 
                  :max="currentPlayer?.balance || 0" 
                  class="px-2 py-1 border rounded w-24 text-right" 
                />
                <span class="ml-2">chips</span>
              </div>
              
              <!-- Quick bet buttons -->
              <div class="flex space-x-2 mt-2">
                <button 
                  @click="betAmount = calculatePotPercentage(0.5)" 
                  class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300"
                >
                  1/2 Pot
                </button>
                <button 
                  @click="betAmount = calculatePotPercentage(0.75)" 
                  class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300"
                >
                  3/4 Pot
                </button>
                <button 
                  @click="betAmount = calculatePotPercentage(1)" 
                  class="px-2 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300"
                >
                  Pot
                </button>
              </div>
            </div>
            
            <!-- Bet/Raise button -->
            <button
              @click="takeAction(availableActions.includes('bet') ? 'bet' : 'raise', betAmount)"
              class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            >
              {{ availableActions.includes('bet') ? 'Bet' : 'Raise' }}
            </button>
          </div>
        </div>
  
        <!-- Game log -->
        <div class="mt-4">
          <h2 class="font-bold mb-2">Game Log</h2>
          <div class="bg-gray-100 p-2 rounded h-32 overflow-y-auto">
            <p v-for="(log, index) in gameLogs" :key="index" class="text-sm">
              {{ log }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { defineComponent } from 'vue';
  import Pusher from 'pusher-js';
  
  export default defineComponent({
    props: {
      table: Object,
      seats: Array,
      currentUserSeat: Object,
      isHost: Boolean
    },
    
    data() {
      return {
        pusher: null,
        channel: null,
        secret: null,
        playerCards: [],
        community: [],
        currentHand: null,
        currentTurnSeatId: null,
        currentDealer: null,
        currentRound: null,
        currentPot: 0,
        isPlayerTurn: false,
        availableActions: [],
        betAmount: 0,
        minBetAmount: 10, // Default minimum bet
        seatActions: {},
        gameLogs: [],
        userCurrentSeat: this.currentUserSeat, // Initialize from prop
        currentPlayer: null // Will store the current player object with balance
      };
    },
    
    computed: {
      hasEnoughPlayers() {
        return this.seats.filter(seat => seat.isOccupied).length >= 2;
      },
      
      // Calculate pot percentage for quick bet buttons
      calculatePotPercentage() {
        return (percentage) => {
          return Math.min(
            Math.floor(this.currentPot * percentage), 
            this.currentPlayer?.balance || 0
          );
        };
      }
    },
    
    mounted() {
      this.connectToPusher();
      this.addLog('Welcome to the poker table!');
      
      this.fetchTableState();
  
      // Get current player information if seated
      if (this.userCurrentSeat) {
        this.getCurrentPlayerInfo();
      }
    },
    
    beforeUnmount() {
      // Clean up Pusher connections
      if (this.secret) {
        this.pusher.unsubscribe('table.' + this.table.id + '.seat.' + this.userCurrentSeat.id);
      }
      if (this.channel) {
        this.pusher.unsubscribe('table.' + this.table.id);
      }
      if (this.pusher) {
        this.pusher.disconnect();
      }
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
        
        // Subscribe to private channel if user has a seat
        if (this.userCurrentSeat) {
          this.secret = this.pusher.subscribe('table.' + this.table.id + '.seat.' + this.userCurrentSeat.id);
          this.secret.bind('cards.dealt', (data) => {
            this.handleCardsDealt(data.seatId, data.cards);
          });
        }
        
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
        
        this.channel.bind('action.taken', (data) => {
          this.handleActionTaken(data.seatId, data.action, data.amount);
        });
      },
  
      async fetchTableState() {
        try {
            const tableId = this.table.id;
            const response = await axios.get(`/tables/${tableId}/state`);
            const data = response.data;

            this.currentHand = data.hand;
            this.currentRound = data.round;
            this.community = data.community_cards || [];
            this.currentPot = data.pot?.total || 0;
            this.currentDealer = data.table?.dealer_seat_id || null;
            this.seatActions = data.seats || [];
            this.lastAction = data.last_action || null;

            // Optional: Add any additional state population you need
        } catch (error) {
            console.error("Error fetching table state:", error);
        }
      },
  
      toggleTableStatus() {
        fetch(`/tables/${this.table.id}/toggle-status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to toggle table status');
          return response.json();
        })
        .then(data => {
          this.addLog(`Table status changed to: ${data.status}`);
          this.table.status = data.status;
        })
        .catch(error => {
          console.error('Error toggling table status:', error);
          this.addLog('Error toggling table status: ' + error.message);
        });
      },
  
      // Get current player information
      getCurrentPlayerInfo() {
        fetch(`/players/me`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to get player info');
          return response.json();
        })
        .then(data => {
          this.currentPlayer = data.player;
          this.betAmount = Math.min(this.minBetAmount, this.currentPlayer.balance);
        })
        .catch(error => {
          console.error('Error getting player info:', error);
        });
      },
  
      // Handle user actions
      joinSeat(seatId) {
        fetch(`/seats/${seatId}/join`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to join seat');
          return response.json();
        })
        .then(data => {
          this.addLog('You joined seat #' + seatId);
          // After joining, reconnect to Pusher to get private channel
          this.userCurrentSeat = { id: seatId };
          this.connectToPusher();
          this.getCurrentPlayerInfo();
        })
        .catch(error => {
          console.error('Error joining seat:', error);
          this.addLog('Error joining seat: ' + error.message);
        });
      },
      
      leaveSeat() {
        if (!this.userCurrentSeat) return;
        
        fetch(`/seats/${this.userCurrentSeat.id}/leave`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to leave seat');
          return response.json();
        })
        .then(data => {
          this.addLog('You left your seat');
          // Clean up private channel subscription
          if (this.secret) {
            this.pusher.unsubscribe('table.' + this.table.id + '.seat.' + this.userCurrentSeat.id);
            this.secret = null;
          }
          this.userCurrentSeat = null;
          this.playerCards = [];
          this.isPlayerTurn = false;
          this.availableActions = [];
          this.currentPlayer = null;
        })
        .catch(error => {
          console.error('Error leaving seat:', error);
          this.addLog('Error leaving seat: ' + error.message);
        });
      },
      
      startHand() {
        fetch(`/tables/${this.table.id}/start-hand`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to start hand');
          return response.json();
        })
        .then(data => {
          this.addLog('Starting new hand...');
        })
        .catch(error => {
          console.error('Error starting hand:', error);
          this.addLog('Error starting hand: ' + error.message);
        });
      },
      
      getAvailableActions() {
        if (!this.userCurrentSeat || !this.currentHand) return;
        
        fetch(`/tables/${this.table.id}/hands/${this.currentHand}/actions`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => {
          if (!response.ok) throw new Error('Failed to get available actions');
          return response.json();
        })
        .then(data => {
          console.log('Available actions:', data.actions);
          this.availableActions = data.actions;
          
          // Initialize bet amount based on available actions
          if (this.availableActions.includes('bet') || this.availableActions.includes('raise')) {
            this.betAmount = Math.min(this.minBetAmount, this.currentPlayer?.balance || 0);
          }
        })
        .catch(error => {
          console.error('Error getting available actions:', error);
          this.addLog('Error getting available actions: ' + error.message);
        });
      },
      
      takeAction(actionType, amount = 0) {
        if (!this.userCurrentSeat || !this.currentHand) return;
        
        fetch(`/tables/${this.table.id}/hands/${this.currentHand}/actions`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            player_id: this.currentPlayer.id,
            action_type: actionType,
            amount: amount
          })
        })
        .then(async response => {
          const data = await response.json().catch(() => ({}));
          if (!response.ok) throw new Error('Failed to take action');
          return response.json();
        })
        .then(data => {
          this.addLog(`You ${actionType}${amount > 0 ? ' $' + amount : ''}`);
          this.isPlayerTurn = false;
          this.availableActions = [];
          
          // Update seatActions to show the action immediately
          if (this.userCurrentSeat) {
            this.seatActions[this.userCurrentSeat.id] = {
              type: actionType,
              amount: amount
            };
          }
        })
        .catch(error => {
          console.error('Error taking action:', error);
          this.addLog('Error taking action: ' + error.message);
        });
      },
      
      // Event handlers
      handleSeatUpdate(seatData) {
        const seatIndex = this.seats.findIndex(s => s.id === seatData.id);
        if (seatIndex !== -1) {
          this.seats[seatIndex] = { ...this.seats[seatIndex], ...seatData };
          
          const action = seatData.isOccupied ? 'joined' : 'left';
          this.addLog(`${seatData.userName || 'A player'} ${action} seat #${seatData.position}`);
        }
      },
      
      handleStatusUpdate(status) {
        this.table.status = status;
        this.addLog(`Table status changed to: ${status}`);
      },
      
      handleTurnChange(seatId) {
        this.currentTurnSeatId = seatId;
        // Check if it's the current user's turn
        if (this.userCurrentSeat && this.userCurrentSeat.id === seatId) {
          this.isPlayerTurn = true;
          this.getAvailableActions();
          this.addLog('It\'s your turn!');
        } else {
          const seat = this.seats.find(s => s.id === seatId);
          if (seat) {
            this.addLog(`It's ${seat.userName}'s turn`);
          }
          this.isPlayerTurn = false;
          this.availableActions = [];
        }
      },
      
      handleActionTaken(seatId, action, amount) {
        const seat = this.seats.find(s => s.id === seatId);
        if (seat) {
          this.seatActions[seatId] = { type: action, amount: amount };
          this.addLog(`${seat.userName} ${action}${amount > 0 ? ' $' + amount : ''}`);
          
          // Update pot
          if (['bet', 'call', 'raise', 'allin'].includes(action) && amount > 0) {
            this.currentPot += amount;
          }
        }
      },
      
      handleRoundAdvance(roundType, cards) {
        this.currentRound = roundType;
        
        if (cards) {
          switch (roundType) {
            case 'preflop':
              this.addLog('Preflop round started');
              break;
            case 'flop':
              this.community = cards.slice(0, 3);
              this.addLog(`Flop: ${this.community.join(' ')}`);
              break;
            case 'turn':
              this.community.push(cards[0]);
              this.addLog(`Turn: ${cards[0]}`);
              break;
            case 'river':
              this.community.push(cards[0]);
              this.addLog(`River: ${cards[0]}`);
              break;
          }
        }
        
        // Reset seat actions for the new round
        this.seatActions = {};
      },
      
      handleHandStarted(handId, data) {
        this.currentHand = handId;
        this.currentDealer = data.dealer;
        this.currentRound = 'preflop';
        this.community = [];
        this.playerCards = [];
        this.seatActions = {};
        this.currentPot = data.pot || 0;
        
        // Reset UI state
        this.isPlayerTurn = false;
        this.availableActions = [];
        
        this.addLog(`Hand #${handId} started - Dealer seat #${data.dealer}`);
        this.addLog(`Small blind: seat #${data.small_blind}, Big blind: seat #${data.big_blind}`);
        
        // Update player info after hand starts
        if (this.userCurrentSeat) {
          this.getCurrentPlayerInfo();
        }
      },
      
      handleHandFinished(handId, winners) {
        this.addLog(`Hand #${handId} finished`);
        
        winners.forEach(winner => {
          const seat = this.seats.find(s => s.id === winner.seat_id);
          if (seat) {
            this.addLog(`${seat.userName} won $${winner.amount}`);
          }
        });
        
        // Reset state
        this.currentHand = null;
        this.currentTurnSeatId = null;
        this.currentRound = null;
        this.community = [];
        this.playerCards = [];
        this.seatActions = {};
        this.currentPot = 0;
        this.isPlayerTurn = false;
        this.availableActions = [];
        
        // Update player info after hand finishes
        if (this.userCurrentSeat) {
          this.getCurrentPlayerInfo();
        }
      },
      
      handleCardsDealt(seatId, cards) {
        // Only process if the cards are for the current user
        if (this.userCurrentSeat && this.userCurrentSeat.id === seatId) {
          this.playerCards = [cards.card1, cards.card2];
          this.addLog(`You received: ${cards.card1} ${cards.card2}`);
        }
      },
      
      // UI helpers
      getSeatPositionStyle(position) {
        // Calculate positions around the circular table
        const maxSeats = this.table.maxSeats || 8; // Default to 8 if not specified
        const angle = (position / maxSeats) * 2 * Math.PI;
        const radius = 120; // in pixels
        
        // Calculate position using trigonometry
        const top = 50 + 40 * Math.sin(angle);
        const left = 50 + 40 * Math.cos(angle);
        
        return {
          top: `${top}%`,
          left: `${left}%`,
          transform: 'translate(-50%, -50%)'
        };
      },
      
      addLog(message) {
        const timestamp = new Date().toLocaleTimeString();
        this.gameLogs.unshift(`[${timestamp}] ${message}`);
        
        // Keep log size manageable
        if (this.gameLogs.length > 50) {
          this.gameLogs = this.gameLogs.slice(0, 50);
        }
      }
    }
  });
  </script>
  
  <style scoped>
  .poker-table-container {
    max-width: 1000px;
    margin: 0 auto;
  }
  
  .seat {
    width: 4rem;
    height: 4rem;
    position: absolute;
    transform: translate(-50%, -50%);
    border: 2px solid #333;
  }
  
  .poker-table {
    position: relative;
    height: 300px;
  }
  </style>