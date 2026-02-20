<template>
  <div class="flex h-screen bg-[#ecf0f5] overflow-hidden">

    <!-- ================= SIDEBAR ================= -->
    <aside
      :class="[
        'bg-white shadow-md transition-all duration-300 flex flex-col',
        collapsed ? 'w-20' : 'w-64'
      ]"
    >

      <!-- Logo / Toggle -->
      <div class="h-16 flex items-center justify-between px-4 border-b">
        <div class="flex items-center gap-2 min-w-0">
          <img :src="logoUrl" alt="Vilcom Logo" class="h-8 w-8 object-contain" />
          <div v-if="!collapsed" class="leading-tight min-w-0">
            <div class="font-extrabold text-sm text-[#1e3a8a]">AMS</div>
            <div class="text-[10px] text-gray-600 truncate">Vilcom Asset Management System</div>
          </div>
        </div>

        <button
          @click="toggleSidebar"
          class="text-gray-600 hover:text-black"
        >
          ☰
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-2 space-y-1">
        <router-link class="nav-item" to="/dashboard/admin">
          📊 <span v-if="!collapsed">Dashboard</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/assets">
          💻 <span v-if="!collapsed">Assets</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/components">
          🖥️ <span v-if="!collapsed">Components</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/licenses">
          💾 <span v-if="!collapsed">Licenses</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/accessories">
          ⌨️ <span v-if="!collapsed">Accessories</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/consumables">
          📦 <span v-if="!collapsed">Consumables</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/people">
          👥 <span v-if="!collapsed">People</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/maintenances">
          🛠️ <span v-if="!collapsed">Maintenances</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/logs">
          📜 <span v-if="!collapsed">Activity Logs</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/transfers/assets">
          🔄 <span v-if="!collapsed">Transfer Assets</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/transfers/returns">
          ↩️ <span v-if="!collapsed">Return Assets</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/tickets">
          ⚠️ <span v-if="!collapsed">User Issues</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/reports">
          📁 <span v-if="!collapsed">Reports</span>
        </router-link>

        <router-link class="nav-item" to="/dashboard/admin/settings">
          ⚙️ <span v-if="!collapsed">Settings</span>
        </router-link>

      </nav>

    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="flex flex-col flex-1">

      <!-- HEADER -->
      <header class="h-16 bg-white shadow flex items-center justify-between px-6">

        <h1 class="font-semibold text-lg">
          Admin Panel
        </h1>

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

const toggleSidebar = () => collapsed.value = !collapsed.value

const logout = async () => {
  try {
    const token = localStorage.getItem('user_token')
    if (token) {
      await axios.post(
        '/api/logout',
        {}, // empty body
        { headers: { Authorization: `Bearer ${token}` } }
      )
    }
  } catch (err) {
    console.error('Logout API failed', err)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_data')
    user.value = null
    router.push({ name: 'login' });
 // make sure your login route exists
  }
}

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
