import { createI18n } from 'vue-i18n'

// Get initial locale from URL
function getInitialLocale() {
    const path = window.location.pathname
    const segments = path.split('/')
    const locale = segments[1]
    return ['en', 'lv'].includes(locale) ? locale : 'en'
}

const i18n = createI18n({
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: {}, // Will be loaded dynamically
    legacy: false, // Use Composition API mode
    globalInjection: true
})

export default i18n