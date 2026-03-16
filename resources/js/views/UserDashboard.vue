<template>
  <div class="p-6 bg-gray-100 min-h-screen">

    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
      <p class="text-gray-600">Welcome back, {{ userName }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-6 flex items-center">
        <div class="rounded-full bg-blue-100 p-4 mr-4">
          <i class="fa fa-laptop text-blue-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Assigned Assets</p>
          <p class="text-2xl font-black text-gray-800">
            {{ stats.assets }}
          </p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500 p-6 flex items-center">
        <div class="rounded-full bg-purple-100 p-4 mr-4">
          <i class="fa fa-save text-purple-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Licenses</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.licenses }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-purple-500 p-6 flex items-center">
        <div class="rounded-full bg-purple-100 p-4 mr-4">
          <i class="fa fa-save text-purple-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Components</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.components }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-6 flex items-center">
        <div class="rounded-full bg-green-100 p-4 mr-4">
          <i class="fa fa-keyboard text-green-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Accessories</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.accessories }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-yellow-500 p-6 flex items-center">
        <div class="rounded-full bg-yellow-100 p-4 mr-4">
          <i class="fa fa-tint text-yellow-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">My Consumables</p>
          <p class="text-2xl font-black text-gray-800">{{ stats.consumables }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-6 flex items-center">
        <div class="rounded-full bg-red-100 p-4 mr-4">
          <i class="fa fa-ticket text-red-600 text-2xl"></i>
        </div>
        <div>
          <p class="text-sm text-gray-500 uppercase font-bold">Open Support Tickets</p>
          <p class="text-2xl font-black text-gray-800">
            {{ stats.tickets }}
          </p>
        </div>
      </div>

    </div>

    <!-- Recent Assets Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">

      <div class="p-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-bold text-gray-700 uppercase text-sm">
          Recent Equipment Assigned
        </h3>
        <router-link
          to="/dashboard/user/my-assets"
          class="text-blue-600 text-xs font-bold hover:underline"
        >
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
              <th class="p-4">Category</th>
              <th class="p-4">Status</th>
            </tr>
          </thead>

          <tbody>

            <!-- Loading -->
            <tr v-if="loading">
              <td colspan="5" class="p-8 text-center">
                <Loader />
              </td>
            </tr>

            <!-- Assets -->
            <template v-else>
          <tr
            v-for="asset in recentAssets"
            :key="asset.id"
            class="border-t border-gray-50 hover:bg-gray-50"
          >
            <td class="p-4 text-gray-800 font-medium">
              {{ asset.asset_tag }}
            </td>

            <td class="p-4 text-gray-500">
              {{ asset.model }}
            </td>

            <td class="p-4 font-mono text-xs text-gray-500">
              {{ asset.serial }}
            </td>

            <td class="p-4 text-gray-600">
              {{ asset.category }}
            </td>

            <td class="p-4">
              <span
                class="px-2 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 uppercase"
              >
                {{ asset.status_name }}
              </span>
            </td>
          </tr>
              <!-- Empty State -->
              <tr v-if="recentAssets.length === 0">
                <td colspan="5" class="p-8 text-center text-gray-400 italic">
                  No assets assigned yet.
                </td>
              </tr>
            </template>

          </tbody>
        </table>
      </div>
    </div>

    <!-- Other Assigned Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mt-6">
      <!-- Recent Licenses -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b"><h3 class="font-bold text-gray-700 uppercase text-sm">My Licenses</h3></div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-[10px] uppercase font-black text-gray-400">
              <tr><th class="p-4">Name</th><th class="p-4">Product Key</th></tr>
            </thead>
            <tbody>
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentLicenses" :key="item.id" class="border-t hover:bg-gray-50">
                <td class="p-4 font-bold text-purple-600">{{ item.name }}</td>
                <td class="p-4 font-mono text-xs">{{ item.product_key }}</td>
              </tr>
              <tr v-if="!loading && recentLicenses.length === 0"><td colspan="2" class="p-8 text-center text-gray-400 italic">No licenses assigned.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Accessories -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b"><h3 class="font-bold text-gray-700 uppercase text-sm">My Accessories</h3></div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-[10px] uppercase font-black text-gray-400">
              <tr><th class="p-4">Name</th><th class="p-4">Category</th></tr>
            </thead>
            <tbody>
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentAccessories" :key="item.id" class="border-t hover:bg-gray-50">
                <td class="p-4 font-bold text-green-600">{{ item.name }}</td>
                <td class="p-4">{{ item.category }}</td>
              </tr>
              <tr v-if="!loading && recentAccessories.length === 0"><td colspan="2" class="p-8 text-center text-gray-400 italic">No accessories assigned.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Consumables -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b"><h3 class="font-bold text-gray-700 uppercase text-sm">My Consumables</h3></div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-[10px] uppercase font-black text-gray-400">
              <tr><th class="p-4">Item</th><th class="p-4">Category</th></tr>
            </thead>
            <tbody>
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentConsumables" :key="item.id" class="border-t hover:bg-gray-50">
                <td class="p-4 font-bold text-yellow-600">{{ item.item_name }}</td>
                <td class="p-4">{{ item.category }}</td>
              </tr>
              <tr v-if="!loading && recentConsumables.length === 0"><td colspan="2" class="p-8 text-center text-gray-400 italic">No consumables assigned.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Components -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b"><h3 class="font-bold text-gray-700 uppercase text-sm">My Components</h3></div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-[10px] uppercase font-black text-gray-400">
              <tr><th class="p-4">Name</th><th class="p-4">Category</th></tr>
            </thead>
            <tbody>
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentComponents" :key="item.id" class="border-t hover:bg-gray-50">
                <td class="p-4 font-bold text-indigo-600">{{ item.name }}</td>
                <td class="p-4">{{ item.category }}</td>
              </tr>
              <tr v-if="!loading && recentComponents.length === 0"><td colspan="2" class="p-8 text-center text-gray-400 italic">No components assigned.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Loader from '@/components/Loader.vue'


const stats = ref({
  assets: 0,
  tickets: 0,
  licenses: 0,
  accessories: 0,
  consumables: 0,
  components: 0
})

const userName = ref('User')
const recentAssets = ref([])
const recentLicenses = ref([])
const recentAccessories = ref([])
const recentConsumables = ref([])
const recentComponents = ref([])
const loading = ref(true)

const fetchDashboardData = async () => {
  loading.value = true

  try {
    const [statsResponse, userResponse] = await Promise.all([
      axios.get('/api/user-stats'),
      axios.get('/api/user')
    ])

    // Set user name
    userName.value = userResponse?.data?.name ?? 'User'

    const data = statsResponse?.data ?? {}
    console.info("this is the page")
    console.log('Dashboard API Data:', data)



    // Stats
    stats.value.assets = data.my_assets_count ?? 0
    stats.value.tickets = data.open_tickets_count ?? 0
    stats.value.licenses = data.my_licenses_count ?? 0
    stats.value.accessories = data.my_accessories_count ?? 0
    stats.value.consumables = data.my_consumables_count ?? 0
    stats.value.components = data.my_components_count ?? 0


    // Assets (handle nested data if paginated)
    let assetsArray = []

    if (Array.isArray(data.recent_assets)) {
      assetsArray = data.recent_assets
    } else if (Array.isArray(data.recent_assets?.data)) {
      assetsArray = data.recent_assets.data
    }

    recentAssets.value = assetsArray.slice(0, 5)
    recentLicenses.value = (data.recent_licenses || []).slice(0, 5)
    recentAccessories.value = (data.recent_accessories || []).slice(0, 5)
    recentConsumables.value = (data.recent_consumables || []).slice(0, 5)
    recentComponents.value = (data.recent_components || []).slice(0, 5)

  } catch (error) {
    console.error('User dashboard error:', error)

    stats.value.assets = 0
    stats.value.tickets = 0
    stats.value.licenses = 0
    stats.value.accessories = 0
    stats.value.consumables = 0
    stats.value.components=0
    recentAssets.value = []

  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboardData)
</script>