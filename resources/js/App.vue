<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useTheme } from './composables/useTheme'
import './styles/theme.css'

const { init } = useTheme()

onMounted(() => {
  init()
})
</script>

<style>
* {
  box-sizing: border-box;
}
body {
  margin: 0;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}
#app {
  min-height: 100vh;
}
</style>
