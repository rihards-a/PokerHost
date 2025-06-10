import { ref, computed } from 'vue'

const preferredLocale = ref('en')

export function usePreferredLocale() {
  // Initialize from storage if not already done
  if (typeof window !== 'undefined' && preferredLocale.value === 'en') {
    preferredLocale.value = localStorage.getItem('preferred-locale') || 'en'
  }
  
  const setPreferredLocale = (locale) => {
    preferredLocale.value = locale
    if (typeof window !== 'undefined') {
      localStorage.setItem('preferred-locale', locale)
    }
  }
  
  const getLocalizedRoute = (path) => {
    return preferredLocale.value === 'en' ? path : `/${preferredLocale.value}${path}`
  }
  
  return {
    preferredLocale: computed(() => preferredLocale.value),
    setPreferredLocale,
    getLocalizedRoute
  }
}