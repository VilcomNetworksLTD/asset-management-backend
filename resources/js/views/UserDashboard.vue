<template>
  <div class="space-y-10">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Staff <span class="text-vilcom-orange">Workspace</span></h1>
        <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest text-wrap">Welcome back, {{ userName }} • Your inventory and tickets at a glance</p>
      </div>
      <div class="flex items-center gap-3">
         <router-link to="/dashboard/user/report-issue" class="px-5 py-2.5 bg-vilcom-blue text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-transform">
           Report Issue
         </router-link>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
      <StatCard title="My Assets" :value="stats.assets" icon="fa-laptop" bgColor="bg-vilcom-blue" link="/dashboard/user/my-assets" />
      <StatCard title="Licenses" :value="stats.licenses" icon="fa-save" bgColor="bg-teal-500" link="/dashboard/user/my-licenses" />
      <StatCard title="Components" :value="stats.components" icon="fa-hdd" bgColor="bg-indigo-600" link="/dashboard/user/my-components" />
      <StatCard title="Accessories" :value="stats.accessories" icon="fa-keyboard" bgColor="bg-vilcom-orange" link="/dashboard/user/my-accessories" />
      <StatCard title="Open Tickets" :value="stats.tickets" icon="fa-ticket-alt" bgColor="bg-red-500" link="/dashboard/user/my-tickets" />
    </div>

    <!-- Recent Assets Table -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center">
        <div>
          <h3 class="text-lg font-black text-slate-800 tracking-tight">Recently Assigned Equipment</h3>
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Directly assigned to you</p>
        </div>
        <router-link to="/dashboard/user/my-assets" class="text-vilcom-blue text-xs font-black uppercase tracking-widest hover:underline">
          View All
        </router-link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-8 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Asset Tag</th>
              <th class="px-6 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Model</th>
              <th class="px-6 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Serial</th>
              <th class="px-6 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Category</th>
              <th class="px-8 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest text-right">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-12 text-center"><Loader /></td>
            </tr>
            <template v-else>
              <tr v-for="asset in recentAssets" :key="asset.id" class="hover:bg-blue-50/30 transition-colors group/row">
                <td class="px-8 py-4 font-bold text-vilcom-blue font-mono text-xs">{{ asset.model || asset.Asset_Name || asset.name || asset.barcode }}</td>
                <td class="px-6 py-4 font-bold text-slate-700">{{ asset.model || asset.Asset_Name || asset.name }}</td>
                <td class="px-6 py-4 font-mono text-xs text-gray-400">{{ asset.serial || asset.Serial_No }}</td>
                <td class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ asset.category?.name || asset.category }}</td>
                <td class="px-8 py-4 text-right">
                  <span class="px-3 py-1 rounded-lg text-[10px] font-black bg-green-100 text-green-700 uppercase">{{ asset.status_name || asset.status?.Status_Name || 'Assigned' }}</span>
                </td>
              </tr>
              <tr v-if="recentAssets.length === 0">
                <td colspan="5" class="p-12 text-center text-gray-400 font-bold italic">No assets assigned yet.</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Other Assigned Items Hub -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-10">
      <!-- Recent Licenses -->
      <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 font-black text-[10px] text-gray-400 uppercase tracking-widest bg-slate-50/30">My Licenses</div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-gray-50">
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentLicenses" :key="item.id" class="hover:bg-purple-50/30 transition-colors">
                <td class="px-6 py-4 font-bold text-purple-600 truncate max-w-[150px]">{{ item.name }}</td>
                <td class="px-6 py-4 font-mono text-[10px] text-gray-400 text-right">{{ item.product_key }}</td>
              </tr>
              <tr v-if="!loading && recentLicenses.length === 0"><td class="p-8 text-center text-gray-400 text-xs font-bold italic">None Reserved</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Accessories -->
      <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 font-black text-[10px] text-gray-400 uppercase tracking-widest bg-slate-50/30">Accessories</div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-gray-50">
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentAccessories" :key="item.id" class="hover:bg-orange-50/30 transition-colors">
                <td class="px-6 py-4 font-bold text-vilcom-orange truncate max-w-[150px]">{{ item.name }}</td>
                <td class="px-6 py-4 text-[10px] font-bold text-gray-400 text-right uppercase tracking-widest">{{ item.category }}</td>
              </tr>
              <tr v-if="!loading && recentAccessories.length === 0"><td class="p-8 text-center text-gray-400 text-xs font-bold italic">None Reserved</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Components -->
      <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 font-black text-[10px] text-gray-400 uppercase tracking-widest bg-slate-50/30">Components</div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <tbody class="divide-y divide-gray-50">
              <tr v-if="loading"><td colspan="2" class="p-8 text-center"><Loader /></td></tr>
              <tr v-for="item in recentComponents" :key="item.id" class="hover:bg-indigo-50/30 transition-colors">
                <td class="px-6 py-4 font-bold text-indigo-600 truncate max-w-[150px]">{{ item.name }}</td>
                <td class="px-6 py-4 text-[10px] font-bold text-gray-400 text-right uppercase tracking-widest">{{ item.category }}</td>
              </tr>
              <tr v-if="!loading && recentComponents.length === 0"><td class="p-8 text-center text-gray-400 text-xs font-bold italic">None Reserved</td></tr>
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
import StatCard from '../components/dashboard/StatCard.vue'


const stats = ref({
  assets: 0,
  tickets: 0,
  licenses: 0,
  accessories: 0,
  components: 0
})

const userName = ref('User')
const userRole = ref('')
const recentAssets = ref([])
const recentLicenses = ref([])
const recentAccessories = ref([])
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
    userRole.value = userResponse?.data?.role ?? 'staff'

    const data = statsResponse?.data ?? {}
    console.info("this is the page")
    console.log('Dashboard API Data:', data)



    // Stats
    stats.value.assets = data.my_assets_count ?? 0
    stats.value.tickets = data.open_tickets_count ?? 0
    stats.value.licenses = data.my_licenses_count ?? 0
    stats.value.accessories = data.my_accessories_count ?? 0
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
    recentComponents.value = (data.recent_components || []).slice(0, 5)

  } catch (error) {
    console.error('User dashboard error:', error)

    stats.value.assets = 0
    stats.value.tickets = 0
    stats.value.licenses = 0
    stats.value.accessories = 0
    stats.value.components=0
    recentAssets.value = []

  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboardData) 
</script>