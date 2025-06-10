<template>
    <AppLayout>
        <div class="container mx-auto py-8 px-4">
        <h1 class="text-3xl font-bold mb-6">{{$t('Available Tables')}}</h1>
        
        <div v-if="tables.length === 0" class="text-center py-12">
            <p class="text-gray-600 text-xl">{{ $t('No open tables available at the moment.') }}</p>
            <p class="text-gray-500 mt-2">Why not create one?</p>
        </div>
        
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="table in tables" :key="table.id" 
                class="border rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300">
            <div :class="[
                'bg-gradient-to-r px-4 py-3',
                table.isFull ? 'from-amber-500 to-amber-600' : 'from-green-500 to-teal-600'
            ]">
                <div class="flex justify-between items-center">
                <h2 class="text-white font-bold text-xl truncate">{{ table.name }}</h2>
                <span class="bg-white bg-opacity-20 text-white text-sm px-2 py-1 rounded">
                    {{ table.gameType }}
                </span>
                </div>
            </div>
            
            <div class="p-4">
                <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Players:</span>
                    <span>{{ table.occupiedSeats }} / {{ table.maxSeats }}</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                    class="h-full bg-blue-500" 
                    :style="{ width: (table.occupiedSeats / table.maxSeats * 100) + '%' }"
                    ></div>
                </div>
                </div>
                
                <div class="flex justify-between items-center text-sm text-gray-500">
                <span>Host: {{ table.hostName }}</span>
                <span>{{ table.created }}</span>
                </div>
                
                <div class="mt-4 flex justify-end">
                <Link 
                    :href="`/tables/${table.id}`"
                    :class="[
                    'px-4 py-2 rounded font-medium transition-colors duration-200',
                    table.isFull 
                        ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
                        : 'bg-blue-600 text-white hover:bg-blue-700'
                    ]"
                    :disabled="table.isFull"
                >
                    {{ table.isFull ? 'Table Full' : 'Join Table' }}
                </Link>
                </div>
            </div>
            </div>
        </div>
        </div>
    </AppLayout>
  </template>
  
    <script>
        import { Link } from '@inertiajs/vue3';
        import AppLayout from '@/Layouts/AppLayout.vue';
        import { useI18n } from 'vue-i18n'
        import LanguageSwitcher from '../Components/LanguageSwitcher.vue' // Adjust path as needed

        export default {

            components: {
                LanguageSwitcher,
                AppLayout,
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