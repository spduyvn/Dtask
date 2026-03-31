<template>
  <div class="dashboard" :class="{ 'sidebar-open': sidebarOpen }">
    <div class="sidebar-overlay" aria-hidden="true" @click="sidebarOpen = false"></div>
    <aside class="sidebar">
      <div class="sidebar-header">
        <router-link to="/admin" class="logo" @click="sidebarOpen = false"><img src="../assets/images/icons/dtask.png" width="50%" alt="Dtask" /></router-link>
      </div>
      <nav class="sidebar-nav">
        <router-link to="/admin" class="nav-item" active-class="active" @click="sidebarOpen = false">
          <span class="nav-icon">◉</span>
          <span>Overview</span>
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="active" @click="sidebarOpen = false">
          <span class="nav-icon">👤</span>
          <span>Manage users</span>
        </router-link>
      </nav>
    </aside>
    <div class="main-wrap">
      <header class="header">
        <button type="button" class="btn-menu" aria-label="Open menu" @click="sidebarOpen = true">
          <span class="btn-menu-bar"></span>
          <span class="btn-menu-bar"></span>
          <span class="btn-menu-bar"></span>
        </button>
        <h1 class="page-title">{{ pageTitle }}</h1>
        <div class="header-actions">
          <button type="button" class="btn-theme" :title="isDark ? 'Light mode' : 'Dark mode'" @click="toggleTheme">
            {{ isDark ? '☀' : '🌙' }}
          </button>
          <span class="user-name">{{ user?.name }}</span>
          <span class="role-badge admin">Admin</span>
          <button class="btn-logout" @click="handleLogout">Log out</button>
        </div>
      </header>
      <main class="content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import '../styles/common.css'
import '../styles/admin-dashboard-layout.css'
import { computed, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useTheme } from '../composables/useTheme'

const router = useRouter()
const route = useRoute()
const { user, logout } = useAuth()
const { isDark, toggle: toggleTheme } = useTheme()
const sidebarOpen = ref(false)

const pageTitle = computed(() => {
  const titles = {
    AdminDashboard: 'Overview',
    UserList: 'Manage users',
  }
  return titles[route.name] || 'Overview'
})

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>
