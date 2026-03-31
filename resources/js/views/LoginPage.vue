<template>
  <div class="login-page">
    <div class="login-card">
      <h1 class="title"><img src="../assets/images/icons/dtask.png" width="100%" alt="Dtask" /></h1>
      <p class="subtitle">Sign in to your account</p>

      <form @submit.prevent="handleLogin" class="form">
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="email@example.com"
            required
            autocomplete="email"
            :disabled="loading"
          />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
            :disabled="loading"
          />
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="loading">Signing in...</span>
          <span v-else>Sign in</span>
        </button>

        <p class="form-footer">
          Don't have an account?
          <router-link to="/register" class="link">Create account</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import '../styles/login-page.css'
import '../styles/register-page.css'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { login } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

async function handleLogin() {
  errorMessage.value = ''
  loading.value = true
  try {
    const data = await login(email.value, password.value)
    const role = data?.user?.role
    if (role === 0) {
      router.push('/admin')
    } else {
      router.push('/user')
    }
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.email?.[0] ||
      'Email or password is incorrect.'
  } finally {
    loading.value = false
  }
}
</script>
