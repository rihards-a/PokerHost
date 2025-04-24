<template>
    <div v-if="localSuccess || localError" class="fixed top-4 right-4 z-50 max-w-md">
      <div v-if="localSuccess" 
           class="bg-green-500 text-white px-4 py-3 rounded shadow-lg flex justify-between items-center">
        <div>{{ localSuccess }}</div>
        <button @click="localSuccess = null" class="ml-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
  
      <div v-if="localError" 
           class="bg-red-500 text-white px-4 py-3 rounded shadow-lg flex justify-between items-center mt-2">
        <div>{{ localError }}</div>
        <button @click="localError = null" class="ml-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        localSuccess: this.$page.props.flash.success || null,
        localError: this.$page.props.flash.error || null
      }
    }
  }
  </script>
  