<template>
    <nav class="bg-gray-800 text-white shadow-md">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
          <!-- Logo/Site Name -->
          <div>
            <Link href="/" class="text-xl font-bold hover:text-blue-300 transition">
              {{ $page.props.translations.navigation.site_title }}
            </Link>
          </div>
          
          <!-- Navigation Links -->
          <div class="flex items-center space-x-6">
            <Link href="/" class="hover:text-blue-300 transition">
              {{ $page.props.translations.navigation.home }}
            </Link>
            
             <!-- Language Switcher -->
             <div class="relative group">
              <button class="flex items-center space-x-1 hover:text-blue-300 transition">
                <span>{{ $page.props.translations.navigation.language }}</span>
                <span>({{ $page.props.locale ? $page.props.locale.toUpperCase() : 'EN' }})</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg py-1 z-10 invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                <Link 
                  :href="`/lang/en`" 
                  class="block px-4 py-2 text-gray-800 hover:bg-gray-100"
                  :class="{ 'bg-blue-50 text-blue-700': $page.props.locale === 'en' }"
                  preserve-scroll
                >
                  🇺🇸 English
                </Link>
                <Link 
                  :href="`/lang/lv`" 
                  class="block px-4 py-2 text-gray-800 hover:bg-gray-100"
                  :class="{ 'bg-blue-50 text-blue-700': $page.props.locale === 'lv' }"
                  preserve-scroll
                >
                  🇱🇻 Latviešu
                </Link>
              </div>
            </div>

            <!-- Auth Links -->
            <template v-if="$page.props.auth.user">
              <Link 
                href="/dashboard" 
                class="hover:text-blue-300 transition"
                :class="{ 'text-blue-300': $page.component.startsWith('Dashboard') }"
              >
                {{ $page.props.translations.navigation.dashboard }}
              </Link>
              <div class="relative group">
                <button class="flex items-center space-x-1 hover:text-blue-300 transition">
                  <span>{{ $page.props.auth.user.name }}</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown: Add pt-2 to reduce the "gap" issue -->
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200">
                  <Link href="/profile" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                    Profile
                  </Link>
                  <Link 
                    href="/logout" 
                    method="post" 
                    as="button" 
                    class="block w-full text-left px-4 py-2 text-gray-800 hover:bg-gray-100"
                  >
                    {{ $page.props.translations.navigation.logout }}
                  </Link>
                </div>
              </div>
            </template>
            <template v-else>
              <Link href="/login" class="hover:text-blue-300 transition">
                {{ $page.props.translations.navigation.login }}
              </Link>
              <Link 
                href="/register" 
                class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition"
              >
                {{ $page.props.translations.navigation.register }}
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>
  </template>
  
  <script>
  import { Link } from '@inertiajs/vue3';
  
  export default {
    components: {
      Link
    }
  }
  </script>