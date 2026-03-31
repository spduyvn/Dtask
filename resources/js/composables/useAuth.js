import { ref, computed } from 'vue'
import api from '../api/axios'

const user = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
const token = ref(localStorage.getItem('auth_token') || null)

export function useAuth() {
  const isAuthenticated = computed(() => !!token.value)

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('auth_user', JSON.stringify(data.user))
    return data
  }

  async function register(payload) {
    const { data } = await api.post('/register', payload)
    token.value = data.token
    user.value = data.user
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('auth_user', JSON.stringify(data.user))
    return data
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  async function fetchUser() {
    const { data } = await api.get('/me')
    user.value = data.user
    localStorage.setItem('auth_user', JSON.stringify(data.user))
    return data.user
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchUser,
  }
}
