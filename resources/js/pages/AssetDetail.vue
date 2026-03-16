<script setup>
import { ref, onMounted, h } from 'vue';
import axios from 'axios';
import { useSettings } from '../composables/useSettings';

const props = defineProps({
  id: { type: String, required: true }
});

// Small helper component to keep code clean
const InfoBox = (props) => {
  return h('div', {}, [
    h('span', { class: 'block text-[10px] text-gray-400 uppercase font-black tracking-tighter' }, props.label),
    h('span', { class: 'text-sm font-medium text-slate-700' }, props.value || '---')
  ]);
};

const asset = ref(null);
const loading = ref(true);
const error = ref(null);

// grab currency from settings and helper
const { settings } = useSettings();

function formatMoney(amount) {
  if (amount == null || amount === '') return '---';
  const curr = settings.value?.currency || 'KES';
  return `${curr} ${Number(amount).toLocaleString()}`;
}

// Placeholder image logic based on category
const getAssetImage = (category) => {
  const cat = category?.toLowerCase() || '';
  
  if (cat.includes('laptop') || cat.includes('macbook')) 
    return 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?q=80&w=500';
    
  if (cat.includes('phone') || cat.includes('tablet')) 
    return 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?q=80&w=500';

  if (cat.includes('network') || cat.includes('router')) 
    return 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=500';

  if (cat.includes('printer')) 
    return 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?q=80&w=500';

  if (cat.includes('monitor') || cat.includes('desktop')) 
    return 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?q=80&w=500';

  // Default image for miscellaneous items
  return 'https://images.unsplash.com/photo-1586769852836-bc069f19e1b6?q=80&w=500';
};

onMounted(async () => {
  try {
    const response = await axios.get(`/api/assets/${props.id}`);
    asset.value = response.data;
    console.log("SUCCESS:", response.data);
  } catch (e) {
    console.error("Error fetching asset details:", e);
    error.value = 'Failed to load asset details.';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="p-6 bg-gray-50 min-h-screen font-sans">
    <div v-if="loading">
      <Loader />
    </div>
    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded-lg shadow">{{ error }}</div>

    <div v-else-if="asset" class="max-w-6xl mx-auto space-y-6">
      
      <div class="bg-white rounded-xl shadow-md border-l-8 border-orange-500 overflow-hidden flex flex-col md:flex-row">
        <div class="md:w-1/3 bg-slate-50 flex items-center justify-center p-4">
          <img :src="getAssetImage(asset.Asset_Category)" alt="Asset" class="rounded-lg shadow-sm object-cover h-48 w-full border-2 border-white" />
        </div>

        <div class="md:w-2/3 p-6 flex flex-col justify-between">
          <div>
            <div class="flex justify-between items-start">
              <h1 class="text-3xl font-extrabold text-blue-900 mb-1">{{ asset.Asset_Name }}</h1>
              <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" 
                    :class="asset.status?.Status_Name === 'Active' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                {{ asset.status?.Status_Name || 'In Use' }}
              </span>
            </div>
            <p class="text-orange-600 font-mono font-bold tracking-tighter uppercase mb-4">
              S/N: {{ asset.Serial_No }}
            </p>
          </div>
          
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 border-t pt-4">
            <div>
              <span class="block text-[10px] text-gray-400 uppercase font-bold">Category</span>
              <span class="text-blue-800 font-semibold">{{ asset.Asset_Category }}</span>
            </div>
            <div>
              <span class="block text-[10px] text-gray-400 uppercase font-bold">Current User</span>
              <span class="text-blue-800 font-semibold">{{ asset.user?.name || 'Unassigned' }}</span>
            </div>
            <div>
              <span class="block text-[10px] text-gray-400 uppercase font-bold">Location</span>
              <span class="text-blue-800 font-semibold">{{ asset.location || 'N/A' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-blue-600">
          <h2 class="text-blue-900 font-black uppercase text-sm mb-6 flex items-center">
            <span class="bg-blue-600 text-white p-1 rounded mr-2">⚙️</span> System Configuration
          </h2>
          <div class="grid grid-cols-2 gap-y-4">
            <InfoBox label="Processor" :value="asset.specs?.processor" />
            <InfoBox label="Memory" :value="asset.specs?.memory" />
            <InfoBox label="Storage Type" :value="asset.specs?.storage_type" />
            <InfoBox label="Storage Capacity" :value="asset.specs?.storage_capacity" />
            <InfoBox label="OS" :value="asset.specs?.operating_system" />
            <InfoBox label="IP Address" :value="asset.specs?.ip_address" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-500">
          <h2 class="text-blue-900 font-black uppercase text-sm mb-6 flex items-center">
            <span class="bg-orange-500 text-white p-1 rounded mr-2">💳</span> Financials & Lifecycle
          </h2>
          <div class="grid grid-cols-2 gap-y-4">
            <InfoBox label="Supplier" :value="asset.supplier?.Supplier_Name" />
            <InfoBox label="Purchase Date" :value="asset.Purchase_Date ? new Date(asset.Purchase_Date).toLocaleDateString() : '---'" />
            <InfoBox label="Purchase Price" :value="formatMoney(asset.Price)" />
            <InfoBox label="Current Value" :value="formatMoney(asset.current_value)" />
            <InfoBox label="Warranty Expiry" :value="asset.warranty_expiry ? new Date(asset.warranty_expiry).toLocaleDateString() : '---'" />
          </div>

          <!-- Warranty Image Display -->
          <div v-if="asset.warranty_image_url" class="mt-6 border-t pt-4">
            <h3 class="text-[10px] text-gray-400 uppercase font-black tracking-tighter mb-2">Warranty Document</h3>
            <a :href="asset.warranty_image_url" target="_blank" rel="noopener noreferrer" class="block w-fit">
              <img :src="asset.warranty_image_url" alt="Warranty Document" class="rounded-lg shadow-md border-2 border-gray-200 max-h-60 hover:opacity-80 transition-opacity" />
            </a>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-blue-900 px-6 py-4">
          <h2 class="text-white font-bold tracking-wide">Assignment History Log</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-blue-50 text-blue-900 text-[10px] font-black uppercase tracking-widest">
                <th class="p-4 border-b">User Name</th>
                <th class="p-4 border-b">Date Assigned</th>
                <th class="p-4 border-b">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in asset.activity_logs?.filter(l => l.action === 'Assigned')" :key="log.id" class="border-b hover:bg-orange-50 transition-colors">
                <td class="p-4 font-bold text-slate-700">{{ log.details.replace('Assigned to: ', '').split(' (ID:')[0] }}</td>
                <td class="p-4 text-slate-500">{{ new Date(log.created_at).toLocaleDateString() }}</td>
                <td class="p-4 text-slate-500 italic">Assigned by {{ log.user_name }}</td>
              </tr>
              <tr v-if="!asset.activity_logs?.filter(l => l.action === 'Assigned').length">
                <td colspan="3" class="p-10 text-center text-gray-300 italic">No movement history recorded.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>