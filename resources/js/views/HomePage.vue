<template>
  <div class="home-page">
    <header class="header">
      <h1 class="logo"><img src="../assets/images/icons/dtask.png" width="50%" alt="Dtask" /></h1>
      <div class="user-menu">
        <span class="user-name">{{ user?.name }}</span>
        <button class="btn-logout" @click="handleLogout">Đăng xuất</button>
      </div>
    </header>
    <main class="main">
      <div class="welcome-card">
        <h2>Chào mừng, {{ user?.name }}!</h2>
        <p>Bạn đã đăng nhập thành công.</p>
        <p class="email">{{ user?.email }}</p>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, logout } = useAuth()

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<style scoped>
.home-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.logo {
  font-size: 22px;
  font-weight: 700;
  color: #e94560;
  margin: 0;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-name {
  color: #e2e8f0;
  font-size: 15px;
}

.btn-logout {
  padding: 8px 16px;
  background: rgba(233, 69, 96, 0.2);
  color: #e94560;
  border: 1px solid rgba(233, 69, 96, 0.4);
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-logout:hover {
  background: rgba(233, 69, 96, 0.3);
}

.main {
  padding: 48px 32px;
  max-width: 640px;
  margin: 0 auto;
}

.welcome-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 32px;
}

.welcome-card h2 {
  margin: 0 0 12px;
  font-size: 24px;
  color: #f1f5f9;
}

.welcome-card p {
  margin: 0;
  color: #94a3b8;
  font-size: 16px;
}

.welcome-card .email {
  margin-top: 8px;
  font-size: 14px;
  color: #64748b;
}
</style>
