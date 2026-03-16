<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import Loader from '@/components/Loader.vue' 

const rows = ref([])
const loading = ref(false)
const departmentName = ref('')

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15,
})

const fetchDepartmentAssets = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/hod/department-assets', {
      params: { page, per_page: pagination.per_page }
    })
    rows.value = data.data?.assets || []
    departmentName.value = data.data?.department_name || 'Department'
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } catch (error) {
    console.error("Fetch failed:", error);
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchDepartmentAssets())

const components = { Loader }
</script>

<template>
<div class="p-6 text-gray-800">
  <h1 class="text-2xl font-bold mb-4">Department: {{ departmentName }}</h1>

  <div class="bg-white rounded shadow border overflow-hidden">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
        <tr>
          <th class="p-3">Asset</th>
          <th class="p-3">Category</th>
          <th class="p-3">Serial No</th>
          <th class="p-3">Assigned Staff</th>
          <th class="p-3">Status</th>
          <th class="p-3">Current Value</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading"><td colspan="6" class="p-4 text-center"><Loader /></td></tr>
        <tr v-else-if="rows.length === 0"><td colspan="6" class="p-4 text-center">No assets found.</td></tr>
        <tr v-for="asset in rows" :key="asset.id" class="border-t hover:bg-gray-50">
          <td class="p-3 font-semibold text-blue-600">{{ asset.Asset_Name }}</td>
          <td class="p-3">{{ asset.Asset_Category }}</td>
          <td class="p-3 font-mono text-xs">{{ asset.Serial_No }}</td>
          <td class="p-3">{{ asset.user?.name || 'Unassigned' }}</td>
          <td class="p-3">{{ asset.status?.Status_Name }}</td>
          <td class="p-3">{{ asset.current_value }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
</template>