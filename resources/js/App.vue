<template>
  <div class="app-wrapper">
    <nav class="main-nav">
      <div class="nav-container">
        <div class="nav-links">
          <template v-if="!isLoggedIn">
            <router-link to="/" class="link">Login</router-link>
            <router-link to="/register" class="link-btn">Get Started</router-link>
          </template>

          <template v-else>
            <span class="user-greeting">Welcome back!</span>
            <button @click="handleLogout" class="link-btn logout-btn">Logout</button>
          </template>
        </div>
      </div>
    </nav>

    <main class="viewport-center">
      <div class="content-stretch">
        <h1 class="brand-header">VILCOM ASSET MANAGEMENT</h1>
        <router-view />
      </div>
    </main>

    <footer class="app-footer">
      &copy; 2026 Vilcom Asset Management. All rights reserved.
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()
const isLoggedIn = ref(false)

// Function to check token existence
const checkAuth = () => {
  isLoggedIn.value = !!localStorage.getItem('user_token')
}

// Watch for route changes (backup check)
watch(() => route.path, () => {
  checkAuth()
})

onMounted(() => {
  checkAuth()
  // Listen for the 'auth-change' event we will fire from Login
  window.addEventListener('auth-change', checkAuth)
})

onUnmounted(() => {
  // Clean up listener to prevent memory leaks
  window.removeEventListener('auth-change', checkAuth)
})

const handleLogout = () => {
  localStorage.removeItem('user_token')
  localStorage.removeItem('user_data')
  isLoggedIn.value = false
  router.push('/')
}
</script>


<style>
/* Global resets for a full-screen feel */
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: 'Inter', system-ui, sans-serif;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); /* Subtle gradient */
}

.app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-nav {
  background: #1e3a8a; /* Deep Blue brand color */
  padding: 1.5rem 0;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  width: 100%;
}

.nav-container {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: flex-end;
  padding: 0 3rem;
}

.nav-links {
  display: flex;
  gap: 2.5rem;
  align-items: center;
}

.user-greeting {
  color: #f1f5f9;
  font-weight: 500;
}

.link {
  text-decoration: none;
  color: #f1f5f9;
  font-weight: 500;
  font-size: 1.1rem;
  transition: color 0.3s ease;
}

.link:hover {
  color: #fb923c; /* Orange accent on hover */
}

.link-btn {
  text-decoration: none;
  background: #f97316; /* Vibrant Orange */
  color: white;
  padding: 0.8rem 2rem;
  border-radius: 10px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  font-family: inherit;
  font-size: 1rem;
  box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3);
}

.logout-btn {
  background: #ef4444; /* Red for logout */
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
}

.viewport-center {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 2rem;
}

.content-stretch {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2.5rem;
}

.brand-header {
  font-size: 2.2rem;
  font-weight: 900;
  color: #1e3a8a;
  letter-spacing: 3px;
  text-transform: uppercase;
  margin: 0;
}

.app-footer {
  text-align: center;
  padding: 2rem;
  color: #64748b;
  font-size: 0.9rem;
  background: rgba(255,255,255,0.5);
}
</style>