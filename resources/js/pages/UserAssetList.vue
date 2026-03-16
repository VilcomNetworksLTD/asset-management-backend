<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">My Assigned Equipment</h1>
      <p class="text-sm text-gray-500">A detailed list of all assets currently assigned to you.</p>
    </div>
    
    <div class="bg-white rounded shadow-lg border-t-4 border-[#3c8dbc]">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 border-b text-[10px] uppercase text-gray-500 font-black">
            <tr>
              <th class="p-4">Asset Tag</th>
              <th class="p-4">Model</th>
              <th class="p-4">Serial</th>
              <th class="p-4">Category</th>
              <th class="p-4">Status</th>
            </tr>
          </thead>
          <tbody>
            <!-- loading state uses the global loader component for consistency -->
            <tr v-if="loading">
              <td colspan="5" class="p-6 text-center">
                <Loader />
              </td>
            </tr>

            <tr v-for="asset in assets" :key="asset.id" class="border-b hover:bg-gray-50">
              <td class="p-4">{{ asset.asset_tag }}</td>
              <td class="p-4">{{ asset.model }}</td>
              <td class="p-4 font-mono text-xs text-gray-400">{{ asset.serial || 'N/A' }}</td>
              <td class="p-4 text-gray-600">{{ asset.category }}</td>
              <td class="p-4">
                <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 uppercase">
                  {{ asset.status?.Status_Name || asset.status_name || 'Assigned' }}
                </span>
              </td>
            </tr>

            <tr v-if="!loading && assets.length === 0">
              <td colspan="5" class="p-8 text-center text-gray-400 italic">No assets assigned yet.</td>
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
      asset_tag: a.asset_tag || ('AST-' + String(a.id).padStart(4, '0')),
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