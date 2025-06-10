<!-- resources/js/Components/LanguageSwitcher.vue -->
<template>
    <div class="language-switcher">
        <select 
            v-model="currentLocale" 
            @change="changeLanguage"
            class="px-8 py-2 border border-gray-300 text-blue-500 rounded-md"
        >
            <option v-for="(name, code) in availableLocales" :key="code" :value="code">
                {{ name }}
            </option>
        </select>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import translationService from '../services/translationService'

export default {
    name: 'LanguageSwitcher',
    setup() {
        const { locale } = useI18n()
        const currentLocale = ref(locale.value)
        const availableLocales = ref({})

        const loadAvailableLocales = async () => {
            availableLocales.value = await translationService.getAvailableLocales()
        }

        const changeLanguage = async () => {
            try {
                await translationService.setLocale(currentLocale.value)
            } catch (error) {
                console.error('Failed to change language:', error)
                // Revert to previous locale on error
                currentLocale.value = locale.value
            }
        }

        onMounted(() => {
            loadAvailableLocales()
        })

        return {
            currentLocale,
            availableLocales,
            changeLanguage
        }
    }
}
</script>