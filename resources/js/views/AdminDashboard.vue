<template>
  <div class="space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :title="stat.title"
        :value="stat.value"
        :icon="stat.icon"
        :bgColor="stat.bgColor"
      />
    </div>

    <!-- Chart & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="lg:col-span-2">
        <RecentActivity :activities="activities" />
      </div>

      <div class="lg:col-span-1">
        <AssetChart title="Assets by Status" :statusDistribution="statusDistribution" />
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
        { title: 'Assets', value: 0, icon: 'fa-barcode', bgColor: 'bg-[#00c0ef]' },
        { title: 'Licenses', value: 0, icon: 'fa-save', bgColor: 'bg-[#bc1c5c]' },
        { title: 'Accessories', value: 0, icon: 'fa-keyboard', bgColor: 'bg-[#f39c12]' },
        { title: 'Consumables', value: 0, icon: 'fa-tint', bgColor: 'bg-[#0073b7]' },
        { title: 'Components', value: 0, icon: 'fa-hdd', bgColor: 'bg-[#00a65a]' },
        { title: 'People', value: 0, icon: 'fa-users', bgColor: 'bg-[#605ca8]' }
      ],
      activities: [],
      statusDistribution: {
        available: 0,
        pending: 0,
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
      this.stats[3].value = data.consumables || 0
      this.stats[4].value = data.components || 0
      this.stats[5].value = data.people || 0

      this.statusDistribution = data.status_distribution || this.statusDistribution
      this.activities = activityResponse.data || []

    } catch (error) {
      console.error("Admin dashboard error:", error)
    }
  }
}
</script>
