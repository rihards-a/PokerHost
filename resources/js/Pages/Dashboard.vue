<template>
    <AppLayout>
      <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
          <h1 v-if="!isAdmin" class="text-3xl font-bold">{{ $page.props.translations.dashboard.title }}</h1>
          <h1 v-if="isAdmin" class="text-3xl font-bold text-red-700">{{ $page.props.translations.dashboard.admin_title }}</h1>
          <button
            v-if="!isAdmin"
            @click="showCreateTableModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ $page.props.translations.dashboard.create_table }}
          </button>
        </div>
  
        <!-- User's Tables Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <h2 v-if="!isAdmin" class="text-xl font-semibold mb-4">{{ $page.props.translations.dashboard.my_tables }}</h2>
          <h2 v-if="isAdmin" class="text-xl font-semibold mb-4 text-red-700">{{ $page.props.translations.dashboard.all_tables }}</h2>
          
          <div v-if="myTables.length === 0" class="text-gray-500 text-center py-6">
            <p>{{ $page.props.translations.dashboard.no_tables_created }}</p>
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full bg-white">
              <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                  <th class="py-3 px-6 text-left">{{ $page.props.translations.dashboard.table_name }}</th>
                  <th class="py-3 px-6 text-center">{{ $page.props.translations.dashboard.game_type }}</th>
                  <th class="py-3 px-6 text-center">{{ $page.props.translations.dashboard.players }}</th>
                  <th class="py-3 px-6 text-center">{{ $page.props.translations.dashboard.status }}</th>
                  <th class="py-3 px-6 text-center">{{ $page.props.translations.dashboard.created }}</th>
                  <th class="py-3 px-6 text-center">{{ $page.props.translations.dashboard.actions }}</th>
                </tr>
              </thead>
              <tbody class="text-gray-600 text-sm">
                <tr v-for="table in myTables" :key="table.id" class="border-b border-gray-200 hover:bg-gray-50">
                  <td class="py-3 px-6 text-left">{{ table.name }}</td>
                  <td class="py-3 px-6 text-center">{{ translateGameType(table.gameType) }}</td>
                  <td class="py-3 px-6 text-center">{{ table.occupiedSeats }} / {{ table.maxSeats }}</td>
                  <td class="py-3 px-6 text-center">
                    <span
                      :class="{
                        'bg-green-200 text-green-800': table.status === 'open',
                        'bg-gray-200 text-gray-800': table.status === 'closed'
                      }"
                      class="py-1 px-3 rounded-full text-xs"
                    >
                      {{ translateStatus(table.status) }}
                    </span>
                  </td>
                  <td class="py-3 px-6 text-center">{{ table.created }}</td>
                  <td class="py-3 px-6 text-center">
                    <div class="flex item-center justify-center space-x-2">
                      <Link :href="`/tables/${table.id}`" class="text-blue-600 hover:text-blue-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </Link>
                      <Link 
                        :href="`/tables/${table.id}/toggle-status`" 
                        method="post" 
                        as="button"
                        class="text-yellow-600 hover:text-yellow-900"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                      </Link>
                      <button 
                          @click="openEditModal(table)" 
                          class="text-blue-600 hover:text-blue-900"
                      >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.121 2.121 0 113 3L10.5 15.75H7.5v-3L16.862 3.487zM19.5 21h-15a1.5 1.5 0 01-1.5-1.5v-15A1.5 1.5 0 014.5 3h9a.75.75 0 010 1.5h-9a.75.75 0 00-.75.75v15c0 .414.336.75.75.75h15a.75.75 0 00.75-.75v-9a.75.75 0 011.5 0v9A2.25 2.25 0 0119.5 21z" />
                          </svg>
                      </button>
                      <Link 
                        :href="`/tables/${table.id}`" 
                        method="delete" 
                        as="button"
                        class="text-red-600 hover:text-red-900"
                        @click="confirmDelete(table)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Tables You've Joined Section 
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold mb-4">{{ $page.props.translations.dashboard.tables_joined }}</h2>
          
          <div v-if="joinedTables.length === 0" class="text-gray-500 text-center py-6">
            <p>{{ $page.props.translations.dashboard.no_tables_joined }}</p>
          </div>
          
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="table in joinedTables" :key="table.id" class="border rounded-lg overflow-hidden shadow-sm">
              <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-4 py-3">
                <h3 class="text-white font-bold text-lg">{{ table.name }}</h3>
              </div>
              <div class="p-4">
                <div class="flex justify-between items-center text-sm mb-2">
                  <span>{{ $page.props.translations.dashboard.host }}: {{ table.hostName }}</span>
                  <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                    {{ table.gameType }}
                  </span>
                </div>
                <div class="mt-4 flex justify-end">
                  <Link :href="`/tables/${table.id}`" class="bg-blue-600 text-white hover:bg-blue-700 px-3 py-1 rounded text-sm">
                    {{ $page.props.translations.dashboard.return_to_table }}
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
        -->
      </div>
      
      
      <!-- Create Table Modal -->
      <div v-if="showCreateTableModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showCreateTableModal = false"></div>
          
          <div class="bg-white rounded-lg max-w-md w-full mx-auto shadow-xl z-10 p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium">{{ $page.props.translations.dashboard.create_new_table }}</h3>
              <button @click="showCreateTableModal = false" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="createTable">
              <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.table_name_label }}</label>
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
                <label for="maxSeats" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.maximum_seats }}</label>
                <select
                  id="maxSeats"
                  v-model="tableForm.max_seats"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="2">{{ $page.props.translations.dashboard.players_count.replace(':count', '2') }}</option>
                  <option value="6">{{ $page.props.translations.dashboard.players_count.replace(':count', '6') }}</option>
                  <option value="9">{{ $page.props.translations.dashboard.players_count.replace(':count', '9') }}</option>
                  <option value="10">{{ $page.props.translations.dashboard.players_count.replace(':count', '10') }}</option>
                </select>
                <p v-if="errors.max_seats" class="mt-1 text-sm text-red-600">{{ errors.max_seats }}</p>
              </div>
              
              <div class="mb-4">
                <label for="gameType" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.game_type_label }}</label>
                <select
                  id="gameType"
                  v-model="tableForm['game-type']"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="TexasHoldem">{{ $page.props.translations.dashboard.texas_holdem }}</option>
                </select>
                <p v-if="errors['game-type']" class="mt-1 text-sm text-red-600">{{ errors['game-type'] }}</p>
              </div>
              
              <div class="flex justify-end mt-6 space-x-3">
                <button
                  type="button"
                  @click="showCreateTableModal = false"
                  class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                >
                  {{ $page.props.translations.dashboard.cancel }}
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                  :disabled="processing"
                >
                {{ processing ? $page.props.translations.dashboard.creating : $page.props.translations.dashboard.create }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

            <!-- Edit Table Modal -->
      <div v-if="showEditTableModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
          <div class="flex items-center justify-center min-h-screen px-4">
              <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showEditTableModal = false"></div>
              <div class="bg-white rounded-lg max-w-md w-full mx-auto shadow-xl z-10 p-6">
                  <div class="flex justify-between items-center mb-4">
                      <h3 class="text-lg font-medium">{{ $page.props.translations.dashboard.edit_table }}</h3>
                      <button @click="showEditTableModal = false" class="text-gray-500 hover:text-gray-700">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                      </button>
                  </div>
                  <form @submit.prevent="updateTable">
                      <div class="mb-4">
                          <label for="editName" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.table_name_label }}</label>
                          <input
                              id="editName"
                              v-model="editTableForm.name"
                              type="text"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              required
                          />
                          <p v-if="editErrors.name" class="mt-1 text-sm text-red-600">{{ editErrors.name }}</p>
                      </div>
                      <div class="mb-4">
                          <label for="editMaxSeats" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.maximum_seats }}</label>
                          <select
                              id="editMaxSeats"
                              v-model="editTableForm.max_seats"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              required
                          >
                              <option value="2">{{ $page.props.translations.dashboard.players_count.replace(':count', '2') }}</option>
                              <option value="6">{{ $page.props.translations.dashboard.players_count.replace(':count', '6') }}</option>
                              <option value="9">{{ $page.props.translations.dashboard.players_count.replace(':count', '9') }}</option>
                              <option value="10">{{ $page.props.translations.dashboard.players_count.replace(':count', '10') }}</option>
                          </select>
                          <p v-if="editErrors.max_seats" class="mt-1 text-sm text-red-600">{{ editErrors.max_seats }}</p>
                          <p v-if="selectedTable && selectedTable.occupied_seats > editTableForm.max_seats" class="mt-1 text-sm text-yellow-600">
                            {{ $page.props.translations.dashboard.seats_occupied_warning.replace(':count', selectedTable.occupied_seats) }}
                          </p>
                      </div>
                      <div class="mb-4">
                          <label for="editGameType" class="block text-sm font-medium text-gray-700 mb-2">{{ $page.props.translations.dashboard.game_type_label }}</label>
                          <select
                              id="editGameType"
                              v-model="editTableForm.game_type"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              required
                          >
                              <option value="TexasHoldem">{{ $page.props.translations.dashboard.texas_holdem }}</option>
                          </select>
                          <p v-if="editErrors.game_type" class="mt-1 text-sm text-red-600">{{ editErrors.game_type }}</p>
                      </div>
                      <div class="flex justify-end mt-6 space-x-3">
                          <button
                              type="button"
                              @click="showEditTableModal = false"
                              class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                          >
                            {{ $page.props.translations.dashboard.cancel }}
                          </button>
                          <button
                              type="submit"
                              class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                              :disabled="editProcessing || (selectedTable && selectedTable.occupied_seats > editTableForm.max_seats)"
                          >
                            {{ editProcessing ? $page.props.translations.dashboard.updating : $page.props.translations.dashboard.update }}
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
      },
      isAdmin: {
        type: Boolean,
        default: false,
      },
    },
    
    data() {
      return {
        showCreateTableModal: false,
        showDeleteModal: false,
        tableToDelete: null,
        showEditTableModal: false,
        editProcessing: false,
        selectedTable: null,
        editTableForm: {
            name: '',
            max_seats: 6,
            'game-type': 'TexasHoldem'
        },
        editErrors: {}
      };
    },
    
    setup() {
      const tableForm = useForm({
        name: '',
        'max_seats': 6,
        'game-type': 'TexasHoldem'
      });
      
      const deleteForm = useForm({});
      
      return {
        tableForm,
        deleteForm,
        errors: tableForm.errors,
      };
    },
    
    methods: {
      translateStatus(status) {
          return this.$page.props.translations.dashboard[status] || status;
      },
      
      translateGameType(gameType) {
          if (gameType === 'TexasHoldem') {
              return this.$page.props.translations.dashboard.texas_holdem;
          }
          return gameType;
      },

      createTable() {
        this.tableForm.post('/tables', {
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
        this.deleteForm.delete(`/tables/${this.tableToDelete.id}`, {
          preserveScroll: true,
          onSuccess: () => {
            this.showDeleteModal = false;
            this.tableToDelete = null;
          },
          onError: (errors) => {
            console.error('Delete failed:', errors);
          },
          onFinish: () => {
            // Ensure the modal is closed even if there's an error
            this.showDeleteModal = false;
          }
        });
      }, 
    openEditModal(table) {
        this.selectedTable = table;
        this.editTableForm = {
            name: table.name,
            max_seats: table.max_seats,
            'game-type': table['game-type'] || table.game_type || 'TexasHoldem'
        };
        this.editErrors = {};
        this.showEditTableModal = true;
    },
    
    async updateTable() {
        this.editProcessing = true;
        this.editErrors = {};
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }
            
            const response = await fetch(`/tables/${this.selectedTable.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(this.editTableForm)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                if (errorData.errors) {
                    this.editErrors = errorData.errors;
                } else {
                    throw new Error(errorData.message || 'Failed to update table');
                }
                return;
            }
            
            const data = await response.json();
            
            this.showEditTableModal = false;
            this.showSuccessMessage('Table updated successfully!');
            
            // Refresh the page to show updated data
            window.location.reload();
            
        } catch (error) {
            console.error('Error updating table:', error);
            this.showErrorMessage(error.message || 'An error occurred while updating the table');
        } finally {
            this.editProcessing = false;
        }
    },
        showSuccessMessage(message) {
          console.log('Success:', message);
      },
    
      showErrorMessage(message) {
        console.error('Error:', message);
      }
    }
  }
  </script>