<template>
  <div class="dashboard-home">
    <div class="cards">
      <div class="card">
        <div class="card-value">{{ stats.users }}</div>
        <div class="card-label">Total users</div>
      </div>
      <div class="card">
        <div class="card-value">—</div>
        <div class="card-label">Projects</div>
      </div>
      <div class="card">
        <div class="card-value">—</div>
        <div class="card-label">Tasks</div>
      </div>
    </div>
    <div class="welcome-block">
      <h2>Welcome to Admin Dashboard</h2>
      <p>Manage users and projects from the left menu.</p>
    </div>
  </div>
</template>

<script setup>
import '../../styles/admin-dashboard-home.css'
import { ref, onMounted } from 'vue'
import api from '../../api/axios'

const stats = ref({ users: 0 })

onMounted(async () => {
  try {
    const { data } = await api.get('/users?per_page=1')
    stats.value.users = data.total ?? 0
  } catch {
    stats.value.users = 0
  }
})
</script>
