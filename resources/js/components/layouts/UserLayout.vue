<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">

    <!-- ================= SIDEBAR ================= -->
    <aside :class="['bg-white shadow-md transition-all duration-300 flex flex-col', collapsed ? 'w-20' : 'w-64']">
      <!-- Logo / Toggle -->
      <div class="h-16 flex items-center justify-between px-4 border-b">
        <div class="flex items-center gap-2 min-w-0">
          <img :src="logoUrl" alt="Vilcom Logo" class="h-8 w-8 object-contain" />
          <div v-if="!collapsed" class="leading-tight min-w-0">
            <div class="font-extrabold text-sm text-[#1e3a8a]">AMS</div>
            <div class="text-[10px] text-gray-600 truncate">Vilcom Asset Management System</div>
          </div>
        </div>
        <button @click="toggleSidebar" class="text-gray-600 hover:text-black">☰</button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-2 space-y-1">
        <router-link class="nav-item" to="/dashboard/user">📊 <span v-if="!collapsed">Dashboard</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/my-assets">💻 <span v-if="!collapsed">My Assets</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/my-tickets">🎫 <span v-if="!collapsed">My Tickets</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/report-issue">⚠️ <span v-if="!collapsed">Report Issue</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/profile">👤 <span v-if="!collapsed">Profile</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/settings">⚙️ <span v-if="!collapsed">Settings</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/request-transfer">🔄 <span v-if="!collapsed">Request Transfer</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/request-return">↩️ <span v-if="!collapsed">Request Return</span></router-link>
        <router-link class="nav-item" to="/dashboard/user/inbound-verifications">✅ <span v-if="!collapsed">Inbound Verifications</span></router-link>
      </nav>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="flex flex-col flex-1">
      <!-- HEADER -->
      <header class="h-16 bg-white shadow flex items-center justify-between px-6">
        <h1 class="font-semibold text-lg">User Dashboard</h1>
        <div class="flex items-center gap-4">
          <span class="text-gray-600">{{ user?.name ?? '' }}</span>
          <button
            @click="logout"
            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition"
          >
            Logout
          </button>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="flex-1 overflow-y-auto p-6">
        <router-view v-if="user" />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const collapsed = ref(false)
const user = ref(null)
const router = useRouter()
const logoUrl = '/Vlogo.jpeg'

// Toggle sidebar
const toggleSidebar = () => collapsed.value = !collapsed.value

// Logout function
const logout = async () => {
  try {
    const token = localStorage.getItem('user_token')
    if (!token) {
      // No token, just clear storage and redirect
      localStorage.removeItem('user_token')
      localStorage.removeItem('user_data')
      router.push({ name: 'login' })
      return
    }

    // Call API logout endpoint
    await axios.post('/api/logout', {}, {
      headers: { Authorization: `Bearer ${token}` }
    })

    // Clear local storage
    localStorage.removeItem('user_token')
    localStorage.removeItem('user_data')

    // Redirect to login
    router.push({ name: 'login' })
  } catch (err) {
    console.error('Logout API failed', err)
    // Clear anyway to avoid being stuck
    localStorage.removeItem('user_token')
    localStorage.removeItem('user_data')
    router.push({ name: 'login' })
  }
}

// Load user from localStorage
onMounted(() => {
  const storedUser = localStorage.getItem('user_data')
  if (storedUser) user.value = JSON.parse(storedUser)
})
</script>

<style>
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 8px;
  color: #374151;
  transition: all 0.2s;
}

.nav-item:hover {
  background: #f3f4f6;
}

.router-link-active {
  background: #e5e7eb;
  font-weight: 600;
}
</style>
