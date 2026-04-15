<template>
  <div class="space-y-10">
    <!-- Header Section -->
    <div class="mb-10">
      <h2 class="text-3xl font-black text-slate-800 tracking-tight">Executive <span class="text-vilcom-blue">Overview</span></h2>
      <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest">Real-time system health and inventory metrics</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-6">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :title="stat.title"
        :value="stat.value"
        :icon="stat.icon"
        :bgColor="stat.bgColor"
        :link="stat.link"
      />
    </div>

    <!-- Chart & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch mt-10">

      <div class="lg:col-span-8">
        <RecentActivity :activities="activities" />
      </div>

      <div class="lg:col-span-4">
        <AssetChart title="Inventory Health" :statusDistribution="statusDistribution" />
      </div>

    </div>
  </div>
</template>

<script>
import axios from "axios"
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useWindowFocus } from '@vueuse/core'
import StatCard from '../components/dashboard/StatCard.vue'
import AssetChart from '../components/dashboard/AssetChart.vue'
import RecentActivity from '../components/dashboard/RecentActivity.vue'

export default {
  name: 'AdminDashboard',

  components: {
    StatCard,
    AssetChart,
    RecentActivity
  },

  setup() {
    const isFocused = useWindowFocus()
    const REFRESH_INTERVAL = 30000
    let intervalId = null

    const stats = ref([
      { title: 'Assets', value: 0, icon: 'fa-barcode', bgColor: 'bg-vilcom-blue', link: '/dashboard/admin/assets' },
      { title: 'Licenses', value: 0, icon: 'fa-save', bgColor: 'bg-indigo-600', link: '/dashboard/admin/licenses' },
      { title: 'Accessories', value: 0, icon: 'fa-keyboard', bgColor: 'bg-vilcom-orange', link: '/dashboard/admin/accessories' },
      { title: 'Parts', value: 0, icon: 'fa-hdd', bgColor: 'bg-teal-600', link: '/dashboard/admin/components' },
      { title: 'Tickets', value: 0, icon: 'fa-ticket-alt', bgColor: 'bg-purple-600', link: '/dashboard/admin/tickets' },
      { title: 'Users', value: 0, icon: 'fa-users', bgColor: 'bg-slate-700', link: '/dashboard/admin/people' },
      { title: 'Security', value: 0, icon: 'fa-lock', bgColor: 'bg-red-600', link: '/dashboard/admin/ssl-certificates' }
    ])
    const activities = ref([])
    const statusDistribution = ref({
      ready_to_deploy: 0,
      deployed: 0,
      archived: 0,
    })

    const fetchData = async () => {
      try {
        const [statsResponse, activityResponse] = await Promise.all([
          axios.get('/api/stats'),
          axios.get('/api/activity-logs')
        ])

        const data = statsResponse.data || {}

        stats.value[0].value = data.assets || 0
        stats.value[1].value = data.licenses || 0
        stats.value[2].value = data.accessories || 0
        stats.value[3].value = data.components || 0
        stats.value[4].value = data.tickets || 0
        stats.value[5].value = data.people || 0
        stats.value[6].value = data.ssl_certificates || 0

        statusDistribution.value = data.status_distribution || statusDistribution.value
        activities.value = activityResponse.data || []

      } catch (error) {
        console.error("Admin dashboard error:", error)
      }
    }

    watch(isFocused, (focused) => {
      if (focused) {
        fetchData()
      }
    })

    onMounted(() => {
      fetchData()
      intervalId = setInterval(fetchData, REFRESH_INTERVAL)
    })

    onUnmounted(() => {
      if (intervalId) clearInterval(intervalId)
    })

    return {
      stats,
      activities,
      statusDistribution,
      fetchData
    }
  }
}
</script>
