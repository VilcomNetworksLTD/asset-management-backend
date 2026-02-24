<template>
  <div class="p-6 bg-gray-100 min-h-screen">

    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
      <p class="text-gray-600">Welcome back, {{ userName }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-6 flex items-center">
        <div class="rounded-full bg-blue-100 p-4 mr-4">
          <i class="fa fa-laptop text-blue-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Assigned Assets</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.assets }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-6 flex items-center">
        <div class="rounded-full bg-red-100 p-4 mr-4">
          <i class="fa fa-ticket text-red-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">Open Support Tickets</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.tickets }}</p>
        </div>
      </div>

    </div>

    <!-- Recent Assets Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">

      <div class="p-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-bold text-gray-700 uppercase text-sm">Recent Equipment Assigned</h3>
        <router-link to="/dashboard/user/my-assets" class="text-blue-600 text-xs font-bold hover:underline">
          View All
        </router-link>
      </div>

      <div class="overflow-x-auto">

        <table class="w-full text-left text-sm">

          <thead class="bg-gray-50 text-[10px] uppercase font-black text-gray-400">
            <tr>
              <th class="p-4">Asset Tag</th>
              <th class="p-4">Model</th>
              <th class="p-4">Serial</th>
              <th class="p-4">Status</th>
            </tr>
          </thead>

          <tbody>

            <tr
              v-for="asset in recentAssets"
              :key="asset.id"
              class="border-t border-gray-50 hover:bg-gray-50"
            >
              <td class="p-4 text-blue-600 font-bold">{{ asset.asset_tag }}</td>
              <td class="p-4">{{ asset.model }}</td>
              <td class="p-4 font-mono text-xs text-gray-500">{{ asset.serial }}</td>
              <td class="p-4">
                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 uppercase">
                  {{ asset.status_name || 'Deployed' }}
                </span>
              </td>
            </tr>

            <tr v-if="recentAssets.length === 0">
              <td colspan="4" class="p-8 text-center text-gray-400 italic">
                No assets assigned yet.
              </td>
            </tr>

          </tbody>

        </table>

      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
const stats = ref({
  assets: 0,
  tickets: 0
})

const userName = ref('User')

const recentAssets = ref([])

const loading = ref(true)
const fetchDashboardData = async () => {
  try {
    const [statsResponse, userResponse] = await Promise.all([
      axios.get('/api/user-stats'),
      axios.get('/api/user')
    ])

    const response = statsResponse
    const data = response.data

    userName.value = userResponse?.data?.name || 'User'

    // UPDATE STATS
    stats.value.assets = data.my_assets_count || 0
    stats.value.tickets = data.open_tickets_count || 0

    // UPDATE TABLE DATA
    recentAssets.value = data.recent_assets || []

  } catch (error) {

    console.error("User dashboard error:", error)

    // fallback safety
    recentAssets.value = []

  } finally {

    loading.value = false

  }
}




onMounted(() => {
  fetchDashboardData()
})
</script>
