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

  data() {
    return {
      stats: [
        { title: 'Assets', value: 0, icon: 'fa-barcode', bgColor: 'bg-vilcom-blue', link: '/dashboard/admin/assets' },
        { title: 'Licenses', value: 0, icon: 'fa-save', bgColor: 'bg-indigo-600', link: '/dashboard/admin/licenses' },
        { title: 'Accessories', value: 0, icon: 'fa-keyboard', bgColor: 'bg-vilcom-orange', link: '/dashboard/admin/accessories' },
        { title: 'Parts', value: 0, icon: 'fa-hdd', bgColor: 'bg-teal-600', link: '/dashboard/admin/components' },
        { title: 'Tickets', value: 0, icon: 'fa-ticket-alt', bgColor: 'bg-purple-600', link: '/dashboard/admin/tickets' },
        { title: 'Users', value: 0, icon: 'fa-users', bgColor: 'bg-slate-700', link: '/dashboard/admin/people' },
        { title: 'Security', value: 0, icon: 'fa-lock', bgColor: 'bg-red-600', link: '/dashboard/admin/ssl-certificates' }
      ],
      activities: [],
      statusDistribution: {
        ready_to_deploy: 0,
        deployed: 0,
        archived: 0,
      },
    }
  },

  async mounted() {
    try {
      const [statsResponse, activityResponse] = await Promise.all([
        axios.get('/api/stats'),
        axios.get('/api/activity-logs')
      ])



      const data = statsResponse.data || {}

      this.stats[0].value = data.assets || 0
      this.stats[1].value = data.licenses || 0
      this.stats[2].value = data.accessories || 0
      this.stats[3].value = data.components || 0
      this.stats[4].value = data.tickets || 0
      this.stats[5].value = data.people || 0
      this.stats[6].value = data.ssl_certificates || 0

      this.statusDistribution = data.status_distribution || this.statusDistribution
      this.activities = activityResponse.data || []

    } catch (error) {
      console.error("Admin dashboard error:", error)
    }
  }
}
</script>
