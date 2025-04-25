<template>
    <div class="table-container">
      <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Table header -->
        <div class="bg-white shadow-md rounded-lg mb-8 p-6">
          <div class="flex justify-between items-center">
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
                  tableStatus === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]"
              >
                {{ tableStatus === 'open' ? 'Open' : 'Closed' }}
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
              <p>{{ formatGameType(table.gameType) }}</p>
            </div>
            
            <!-- Seats around the table -->
            <div v-for="seat in displaySeats" :key="seat.id" class="absolute z-20" :style="positionSeat(seat.position, displaySeats.length)">
              <div class="seat-container">
                <div 
                  :class="[
                    'w-20 h-20 rounded-full flex items-center justify-center flex-col',
                    getSeatClass(seat)
                  ]"
                >
                  <template v-if="seat.isOccupied">
                    <span class="font-medium text-sm">{{ seat.userName }}</span>
                    <span class="text-xs">
                      {{ isCurrentUser(seat) ? '(You)' : '' }}
                    </span>
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
        
        <!-- Actions -->
        <div class="mt-8 text-center" v-if="userCurrentSeat">
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
        channel: null
      };
    },
    
    computed: {
      isAtTable() {
        return !!this.userCurrentSeat;
      },
      
      canJoinSeat() {
        return !this.userCurrentSeat;
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
        
        // Bind to events
        this.channel.bind('seat.updated', (data) => {
          this.handleSeatUpdate(data.seat);
        });
        
        this.channel.bind('status.updated', (data) => {
          this.handleStatusUpdate(data.status);
        });
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
          this.displaySeats[index] = updatedSeat;
        }
        
        // Update user's current seat if relevant
        if (this.userCurrentSeat && this.userCurrentSeat.id === updatedSeat.id) {
          if (!updatedSeat.isOccupied) {
            this.userCurrentSeat = null;
          }
        }
      },
      
      handleStatusUpdate(status) {
        this.tableStatus = status;
      },
      
      formatGameType(gameType) {
        if (gameType === 'TexasHoldem') return 'Texas Hold\'em';
        return gameType;
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
        useForm({}).post(route('tables.toggle-status', this.table.id), {
          preserveScroll: true,
          onSuccess: () => {
            // Note: We don't need to update the UI here as we'll receive
            // the update via the Pusher broadcast
          }
        });
      }
    }
  };
  </script>
  
  <style scoped>
  .poker-table-container {
    position: relative;
    width: 100%;
    overflow: hidden;
  }
  
  .seat-container {
    transform: translate(-50%, -50%);
  }
  </style>