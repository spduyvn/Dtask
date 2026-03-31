import { ref, computed } from 'vue'

const STORAGE_KEY = 'dtask_theme'

const theme = ref('light')

function applyTheme(value) {
  const next = value === 'dark' ? 'dark' : 'light'
  theme.value = next
  if (typeof document !== 'undefined' && document.documentElement) {
    document.documentElement.setAttribute('data-theme', next)
  }
  try {
    localStorage.setItem(STORAGE_KEY, next)
  } catch (_) {}
}

export function useTheme() {
  const isDark = computed(() => theme.value === 'dark')

  function init() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      const preferDark = typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)')?.matches
      const value = saved === 'dark' || saved === 'light' ? saved : (preferDark ? 'dark' : 'light')
      applyTheme(value)
    } catch (_) {
      applyTheme('light')
    }
  }

  function toggle() {
    applyTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  return { isDark, toggle, init }
}
