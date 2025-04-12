<template>
    <div class="p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="card in cards"
        :key="card.id"
        :style="{ backgroundColor: card.color }"
        class="p-4 rounded-2xl shadow-md text-center cursor-pointer transition hover:scale-105"
        @click="handleCardClick(card.id)"
      >
        <p class="mb-2 font-semibold text-white">Card {{ card.id }}</p>
        <button
          class="mt-2 text-sm px-3 py-1 rounded bg-white text-black hover:bg-gray-200"
          @click.stop="changeColor(card.id)"
        >
          Change Color
        </button>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'PressableCards',
    data() {
      return {
        cards: Array.from({ length: 12 }, (_, i) => ({
          id: i + 1,
          color: '#3490dc',
        })),
        colors: ['#3490dc', '#38c172', '#e3342f', '#ffed4a', '#6c5ce7'],
      };
    },
    mounted() {  
        
        window.Echo.connector.pusher.connection.bind('state_change', function(states) {
        console.log('Pusher Connection State:', states.current);
        });

        var pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
          cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER
        });

        var channel = pusher.subscribe('my-channel');
        channel.bind('my-event', (e) => {
          console.log('Received event:', e); // Log event data to see if it’s received
            const card = this.cards.find(c => c.id === parseInt(e.card_id, 10));
            if (card) {
                this.changeColor(card.id);
            } else {
                console.error(`No card found for card_id: ${e.card_id}`);
            }
        });
    },
    methods: {
      handleCardClick(cardId) {
        axios.post('/api/box-press', { card_id: cardId })
          .then(() => console.log(`Card ${cardId} pressed.`))
          .catch(error => console.error('Error pressing card:', error));
      },
      changeColor(cardId) {
        const card = this.cards.find(c => c.id === cardId);
        const nextColor = this.colors[(this.colors.indexOf(card.color) + 1) % this.colors.length];
        card.color = nextColor;
      },
    },
  };
  </script>
  
  <style scoped>
  /* Optional extra styling */
  </style>