<template>
    <div>
      <h1>Spawn a Box Globally</h1>
      <button @click="spawnBox">Spawn Box</button>
      <div class="box-container">
        <div
          v-for="(box, index) in boxes"
          :key="index"
          class="box"
        ></div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        boxes: []
      };
    },
    mounted() {
      // Listen for the BoxSpawned event broadcast via Laravel Echo.
      window.Echo.channel('test-channel')
        .listen('BoxSpawned', (e) => {
          // When event is received, push a new box into the boxes array.
          this.boxes.push({});
        });
    },
    methods: {
      spawnBox() {
        // Send an AJAX request to trigger the BoxSpawned event.
        axios.get('/spawn-box')
          .then(response => {
            console.log('Box spawned:', response.data);
          })
          .catch(error => {
            console.error('Error spawning box:', error);
          });
      }
    }
  };
  </script>
  
  <style>
  .box-container {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
  }
  .box {
    width: 100px;
    height: 100px;
    background-color: blue;
    margin: 10px;
  }
  </style>
  