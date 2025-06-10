<template>
    <AppLayout>
      <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold">Dashboard</h1>
          <button
            @click="showCreateTableModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Table
          </button>
        </div>
  
        <!-- User's Tables Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <h2 class="text-xl font-semibold mb-4">My Tables</h2>
          
          <div v-if="myTables.length === 0" class="text-gray-500 text-center py-6">
            <p>You haven't created any tables yet.</p>
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full bg-white">
              <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                  <th class="py-3 px-6 text-left">Name</th>
                  <th class="py-3 px-6 text-center">Game Type</th>
                  <th class="py-3 px-6 text-center">Players</th>
                  <th class="py-3 px-6 text-center">Status</th>
                  <th class="py-3 px-6 text-center">Created</th>
                  <th class="py-3 px-6 text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="text-gray-600 text-sm">
                <tr v-for="table in myTables" :key="table.id" class="border-b border-gray-200 hover:bg-gray-50">
                  <td class="py-3 px-6 text-left">{{ table.name }}</td>
                  <td class="py-3 px-6 text-center">{{ table.gameType }}</td>
                  <td class="py-3 px-6 text-center">{{ table.occupiedSeats }} / {{ table.maxSeats }}</td>
                  <td class="py-3 px-6 text-center">
                    <span
                      :class="{
                        'bg-green-200 text-green-800': table.status === 'open',
                        'bg-gray-200 text-gray-800': table.status === 'closed'
                      }"
                      class="py-1 px-3 rounded-full text-xs"
                    >
                      {{ table.status }}
                    </span>
                  </td>
                  <td class="py-3 px-6 text-center">{{ table.created }}</td>
                  <td class="py-3 px-6 text-center">
                    <div class="flex item-center justify-center space-x-2">
                      <Link :href="getLocalizedRoute(`/tables/${table.id}`)" class="text-blue-600 hover:text-blue-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </Link>
                      <Link 
                        :href="getLocalizedRoute(`/tables/${table.id}/toggle-status`)" 
                        method="post" 
                        as="button"
                        class="text-yellow-600 hover:text-yellow-900"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                      </Link>
                      <Link 
                        :href="`/tables/${table.id}`" 
                        method="delete" 
                        as="button"
                        class="text-red-600 hover:text-red-900"
                        @click.prevent="confirmDelete(table)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </Link>
                    </div>
                  </td>
                  
                  <!-- Add delete confirmation modal -->
                  <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4">
                      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showDeleteModal = false"></div>
                      
                      <div class="bg-white rounded-lg max-w-md w-full mx-auto shadow-xl z-10 p-6">
                        <div class="mb-4">
                          <h3 class="text-lg font-medium text-gray-900">Delete Table</h3>
                          <p class="mt-2 text-sm text-gray-500">
                            Are you sure you want to delete "{{ tableToDelete?.name }}"? This action cannot be undone
                            and all seats associated with this table will also be removed.
                          </p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                          <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            @click="deleteTable"
                            class="px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700"
                            :disabled="deleteForm.processing"
                          >
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Tables You've Joined Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold mb-4">Tables You've Joined</h2>
          
          <div v-if="joinedTables.length === 0" class="text-gray-500 text-center py-6">
            <p>You haven't joined any tables yet.</p>
          </div>
          
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="table in joinedTables" :key="table.id" class="border rounded-lg overflow-hidden shadow-sm">
              <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-4 py-3">
                <h3 class="text-white font-bold text-lg">{{ table.name }}</h3>
              </div>
              <div class="p-4">
                <div class="flex justify-between items-center text-sm mb-2">
                  <span>Host: {{ table.hostName }}</span>
                  <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                    {{ table.gameType }}
                  </span>
                </div>
                <div class="mt-4 flex justify-end">
                  <Link :href="`/tables/${table.id}`" class="bg-blue-600 text-white hover:bg-blue-700 px-3 py-1 rounded text-sm">
                    Return to Table
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Create Table Modal -->
      <div v-if="showCreateTableModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showCreateTableModal = false"></div>
          
          <div class="bg-white rounded-lg max-w-md w-full mx-auto shadow-xl z-10 p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium">Create New Table</h3>
              <button @click="showCreateTableModal = false" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="createTable">
              <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Table Name</label>
                <input
                  id="name"
                  v-model="tableForm.name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
              </div>
              
              <div class="mb-4">
                <label for="maxSeats" class="block text-sm font-medium text-gray-700 mb-2">Maximum Seats</label>
                <select
                  id="maxSeats"
                  v-model="tableForm.max_seats"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="2">2 Players</option>
                  <option value="6">6 Players</option>
                  <option value="9">9 Players</option>
                  <option value="10">10 Players</option>
                </select>
                <p v-if="errors.max_seats" class="mt-1 text-sm text-red-600">{{ errors.max_seats }}</p>
              </div>
              
              <div class="mb-4">
                <label for="gameType" class="block text-sm font-medium text-gray-700 mb-2">Game Type</label>
                <select
                  id="gameType"
                  v-model="tableForm['game-type']"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="TexasHoldem">Texas Hold'em</option>
                </select>
                <p v-if="errors['game-type']" class="mt-1 text-sm text-red-600">{{ errors['game-type'] }}</p>
              </div>
              
              <div class="flex justify-end mt-6 space-x-3">
                <button
                  type="button"
                  @click="showCreateTableModal = false"
                  class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                  :disabled="processing"
                >
                  {{ processing ? 'Creating...' : 'Create Table' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AppLayout>
  </template>
  
<script>
  import { Link, useForm } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import { usePreferredLocale } from '@/composables/usePreferredLocale';

  export default {
    components: {
      AppLayout,
      Link
    },
    
    props: {
      myTables: {
        type: Array,
        default: () => []
      },
      joinedTables: {
        type: Array,
        default: () => []
      }
    },
    
    data() {
      return {
        showCreateTableModal: false,
        showDeleteModal: false,
        tableToDelete: null
      };
    },
    
    setup() {
      const tableForm = useForm({
        name: '',
        'max_seats': 6,
        'game-type': 'TexasHoldem'
      });
      
      const deleteForm = useForm({});
      
      const { getLocalizedRoute } = usePreferredLocale();
      
      return {
        tableForm,
        deleteForm,
        errors: tableForm.errors,
        getLocalizedRoute
      };
    },
    
    methods: {
      createTable() {
        // Use localized route for the POST request
        this.tableForm.post(this.getLocalizedRoute('/tables'), {
          onSuccess: () => {
            this.showCreateTableModal = false;
          }
        });
      },
      
      confirmDelete(table) {
        this.tableToDelete = table;
        this.showDeleteModal = true;
      },
      
      deleteTable() {
        // Use localized route for the DELETE request
        this.deleteForm.delete(this.getLocalizedRoute(`/tables/${this.tableToDelete.id}`), {
          preserveScroll: true,
          onSuccess: () => {
            this.showDeleteModal = false;
            this.tableToDelete = null;
          },
          onError: (errors) => {
            console.error('Delete failed:', errors);
          },
          onFinish: () => {
            this.showDeleteModal = false;
          }
        });
      }
    }
  }
</script>