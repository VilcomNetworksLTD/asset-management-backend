<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Loader from '@/components/Loader.vue' 
import { Eye } from 'lucide-vue-next'

const rows = ref([])
const loading = ref(false)
const departmentName = ref('')

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15,
})

const router = useRouter()

const goToDetail = (id) => {
  router.push({ name: 'user-asset-detail', params: { id } });
};

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
<div class="p-8 space-y-8">
  <div>
    <h1 class="text-3xl font-black text-slate-800 tracking-tight">Department <span class="text-vilcom-blue">Assets</span></h1>
    <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-[0.2em] leading-relaxed">Inventory overview for {{ departmentName }}</p>
  </div>

  <div class="bg-white shadow-sm border border-gray-100 rounded-[2rem] overflow-hidden group transition-all duration-300 hover:shadow-lg">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-slate-50/50">
          <tr class="text-gray-400 font-black text-[10px] uppercase tracking-widest">
            <th class="px-8 py-5">Asset Name</th>
            <th class="px-6 py-5">Category</th>
            <th class="px-6 py-5">Assigned Staff</th>
            <th class="px-6 py-5">Status</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading"><td colspan="5" class="p-12 text-center"><Loader /></td></tr>
          <tr v-else-if="rows.length === 0"><td colspan="5" class="p-12 text-center text-gray-400 font-bold uppercase text-xs tracking-widest">No department assets located.</td></tr>
          
          <tr v-for="asset in rows" :key="asset.id" class="hover:bg-blue-50/30 transition-colors group/row">
            <td class="px-8 py-5">
              <span class="font-black text-slate-800 tracking-tight">{{ asset.Asset_Name }}</span>
            </td>
            <td class="px-6 py-5">
              <span class="text-xs font-bold text-slate-500 uppercase">{{ asset.Asset_Category || asset.category?.name || '-' }}</span>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-2">
                <div class="size-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400">
                  {{ (asset.user?.name || 'S').charAt(0) }}
                </div>
                <span class="text-xs font-bold text-slate-600">{{ asset.user?.name || 'In Stock' }}</span>
              </div>
            </td>
            <td class="px-6 py-5">
              <span :class="['px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm', asset.user ? 'bg-blue-50 text-vilcom-blue' : 'bg-green-50 text-green-600']">
                {{ asset.status?.Status_Name || 'Ready' }}
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <button @click="goToDetail(asset.id)" class="bg-vilcom-blue text-white px-4 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:shadow-lg transition-all active:scale-95 flex items-center gap-2 ml-auto">
                <Eye class="size-3" /> Details
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
</template>