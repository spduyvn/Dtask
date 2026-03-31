<template>
  <div class="login-page">
    <div class="login-card">
      <h1 class="title"><img src="../assets/images/icons/dtask.png" width="100%" alt="Dtask" /></h1>
      <p class="subtitle">Create your account</p>

      <form @submit.prevent="handleRegister" class="form">
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <div class="field">
          <label for="name">Full name</label>
          <input
            id="name"
            v-model="name"
            type="text"
            placeholder="Your name"
            required
            autocomplete="name"
            :disabled="loading"
          />
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
            autocomplete="new-password"
            :disabled="loading"
          />
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm password</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            placeholder="••••••••"
            required
            autocomplete="new-password"
            :disabled="loading"
          />
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="loading">Creating account...</span>
          <span v-else>Create account</span>
        </button>

        <p class="form-footer">
          Already have an account?
          <router-link to="/login" class="link">Sign in</router-link>
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
const { register } = useAuth()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const errorMessage = ref('')

async function handleRegister() {
  errorMessage.value = ''
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Password and confirmation do not match.'
    return
  }
  loading.value = true
  try {
    const data = await register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    if (data?.user?.role === 0) {
      router.push('/admin')
    } else {
      router.push('/user')
    }
  } catch (err) {
    const msg = err.response?.data?.errors
    errorMessage.value = msg
      ? Object.values(msg).flat().join(' ')
      : err.response?.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
