<template>
  <div class="p-8 space-y-10">
    <PageHeader title="My Assigned" highlight="Equipment" />
    
    <div class="bg-white shadow-sm border border-gray-100 rounded-[2.5rem] overflow-hidden group hover:shadow-xl transition-all duration-500">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-8 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Asset Tag</th>
              <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Model</th>
              <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Serial</th>
              <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Category</th>
              <th class="px-8 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-12 text-center">
                 <Loader />
              </td>
            </tr>

            <tr v-for="asset in assets" :key="asset.id" class="hover:bg-blue-50/30 transition-colors group/row">
              <td class="px-8 py-5">
                <span class="font-mono text-xs font-black text-vilcom-blue bg-blue-50 px-3 py-1 rounded-lg border border-blue-100/50">
                  {{ asset.asset_tag }}
                </span>
              </td>
              <td class="px-6 py-5 font-black text-slate-800">{{ asset.model }}</td>
              <td class="px-6 py-5 font-mono text-xs text-gray-400">{{ asset.serial || 'N/A' }}</td>
              <td class="px-6 py-5 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ asset.category }}</td>
              <td class="px-8 py-5 text-right flex justify-end gap-3">
                <router-link 
                  :to="{ name: 'user-asset-detail', params: { id: asset.id } }"
                  class="bg-vilcom-blue text-white px-4 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:shadow-lg transition-all active:scale-95 flex items-center gap-2"
                >
                  <i class="fa fa-eye"></i> Details
                </router-link>
              </td>
            </tr>

            <tr v-if="!loading && assets.length === 0">
              <td colspan="5" class="p-12 text-center text-gray-400 font-black italic uppercase tracking-widest opacity-30">No assets assigned yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import Loader from '@/components/Loader.vue'

const assets = ref([])
const loading = ref(true)

const isFocused = useWindowFocus()

watch(isFocused, (focused) => {
  if (focused) {
    fetchAssets()
  }
})

const fetchAssets = async () => {
  try {
    const { data } = await axios.get('/api/workflow/my-assets')
    // backend now returns a normalized shape, but apply a safety map
    assets.value = (data || []).map(a => ({
      id: a.id,
      asset_tag: a.asset_tag || a.barcode || a.Asset_Name || 'Asset',
      model: a.model || a.Asset_Name || '',
      serial: a.serial || a.Serial_No || '',
      category: a.category || a.Asset_Category || '',
      status: a.status || a.status_name || null,
    }))
  } catch (e) {
    console.error('Failed to load assigned assets', e)
    assets.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchAssets)

// expose loader component so template can use it
const components = { Loader }
</script>