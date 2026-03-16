<template>
  <div class="flex h-screen bg-[#ecf0f5] overflow-hidden">

    <!-- ================= SIDEBAR ================= -->
    <Sidebar :collapsed="collapsed" @toggle="collapsed = !collapsed" />

    <!-- ================= MAIN CONTENT ================= -->
    <div class="flex flex-col flex-1 min-w-0">

      <!-- HEADER -->
      <header class="h-[50px] bg-[#3c8dbc] shadow flex items-center justify-between px-6 flex-shrink-0">
        <h1 class="font-semibold text-base text-white tracking-wide">
          Admin Panel
        </h1>
        <div class="flex items-center gap-4">
          <span class="text-white text-sm">{{ user?.name ?? '' }}</span>
          <button
            @click="logout"
            class="px-3 py-1 bg-white text-[#3c8dbc] text-sm font-semibold rounded hover:bg-gray-100 transition"
          >
            <i class="fa fa-sign-out-alt mr-1"></i> Logout
          </button>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="flex-1 overflow-y-auto p-6">
        <!-- router-view is always mounted; authentication guards handle redirects -->
        <router-view />
      </main>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Sidebar from './Sidebar.vue'

const collapsed = ref(false)
const user = ref(null)
const router = useRouter()

const logout = async () => {
  try {
    const token = localStorage.getItem('user_token')
    if (token) {
      await axios.post('/api/logout', {}, {
        headers: { Authorization: `Bearer ${token}` }
      })
    }
  } catch (err) {
    console.error('Logout API failed', err)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_token')
    localStorage.removeItem('user_data')
    user.value = null
    router.push({ name: 'login' })
  }
}

onMounted(() => {
  const storedUser = localStorage.getItem('user_data')
  if (storedUser) {
    try {
      user.value = JSON.parse(storedUser)
    } catch (err) {
      console.error('Failed to parse user_data from storage', err)
      // corrupted data can break the layout; clear and force login
      localStorage.removeItem('user_data')
      user.value = null
      router.push({ name: 'login' })
    }
  }
})
</script>
