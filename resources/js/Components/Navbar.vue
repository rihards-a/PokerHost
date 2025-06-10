<template>
    <nav class="bg-gray-800 text-white shadow-md">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
          <!-- Logo/Site Name -->
          <div>
            <Link href="/" class="text-xl font-bold hover:text-blue-300 transition">
              Poker Tables
            </Link>
          </div>
          
          <!-- Navigation Links -->
          <div class="flex items-center space-x-6">
            <Link href="/" class="hover:text-blue-300 transition">
              Home
            </Link>
            
            <!-- Auth Links -->
            <template v-if="$page.props.auth.user">
              <Link 
                href="/dashboard" 
                class="hover:text-blue-300 transition"
                :class="{ 'text-blue-300': $page.component.startsWith('Dashboard') }"
              >
                Dashboard
              </Link>
              <div class="relative group">
                <button class="flex items-center space-x-1 hover:text-blue-300 transition">
                  <span>{{ $page.props.auth.user.name }}</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                  <Link href="/profile" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                    Profile
                  </Link>
                  <Link 
                    href="/logout" 
                    method="post" 
                    as="button" 
                    class="block w-full text-left px-4 py-2 text-gray-800 hover:bg-gray-100"
                  >
                    Logout
                  </Link>
                </div>
              </div>
            </template>
            <template v-else>
              <Link href="/login" class="hover:text-blue-300 transition">
                Login
              </Link>
              <Link 
                href="/register" 
                class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition"
              >
                Register
              </Link>
            </template>
              <LanguageSwitcher />
          </div>

        </div>
      </div>
    </nav>
  </template>
  




    <script>
        import { Link } from '@inertiajs/vue3';
        import { useI18n } from 'vue-i18n'
        import LanguageSwitcher from '../Components/LanguageSwitcher.vue' // Adjust path as needed

        export default {

            components: {
                LanguageSwitcher,
                Link
            },
            props: {
                tables: {
                type: Array,
                required: true
        }
            },
            setup(props) {
                const { t } = useI18n()


                return {
                    t,
                }
            }
        }
    </script>