// resources/js/services/translationService.js
import axios from 'axios'
import i18n from '../i18n'
import { router } from '@inertiajs/vue3'

class TranslationService {
    constructor() {
        this.loadedLanguages = []
    }

    async loadTranslations(locale) {
        if (this.loadedLanguages.includes(locale)) {
            return Promise.resolve()
        }

        try {
            const response = await axios.get(`/api/translations/${locale}`)
            const messages = response.data.translations
            
            i18n.global.setLocaleMessage(locale, messages)
            this.loadedLanguages.push(locale)
            
            return Promise.resolve()
        } catch (error) {
            console.error(`Failed to load translations for ${locale}:`, error)
            return Promise.reject(error)
        }
    }

    async setLocale(locale) {
        await this.loadTranslations(locale)
        i18n.global.locale.value = locale
        document.documentElement.lang = locale
        
        // For Inertia.js, we need to navigate to the new locale URL
        const currentPath = window.location.pathname
        const segments = currentPath.split('/')
        
        let newPath
        if (['en', 'lv'].includes(segments[1])) {
            segments[1] = locale
            newPath = segments.join('/')
        } else {
            newPath = `/${locale}${currentPath}`
        }
        
        // Store preference
        localStorage.setItem('preferred-locale', locale)
        
        // Navigate using Inertia
        router.visit(newPath, {
            preserveState: true,
            preserveScroll: true
        })
    }

    getCurrentLocale() {
        return i18n.global.locale.value
    }

    async getAvailableLocales() {
        try {
            const response = await axios.get('/api/locales')
            return response.data.locales
        } catch (error) {
            console.error('Failed to load available locales:', error)
            return { en: 'English', lv: 'Latviešu' }
        }
    }
}

export default new TranslationService()