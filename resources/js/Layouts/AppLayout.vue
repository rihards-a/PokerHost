<template>
  <div class="min-h-screen bg-gray-100">
    <Navbar />
    
    <!-- Flash Messages -->
    <FlashMessages />
    
    <main>
      <slot />
    </main>
    
    <footer class="bg-gray-800 text-white py-6 mt-12">
      <div class="container mx-auto px-4 text-center">
        <p>&copy; {{ new Date().getFullYear() }} {{ $page.props.translations.footer.footer }}</p>
      </div>
    </footer>
  </div>
</template>

<script>
import Navbar from '@/Components/Navbar.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

export default {
  components: {
    Navbar,
    FlashMessages
  }
}
</script>