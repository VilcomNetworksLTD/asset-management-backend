<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Loader from '@/components/Loader.vue'; // Assuming you have a loader

// State
const history = ref([]);
const printers = ref([]); 
const consumablesStock = ref([]); 
const loading = ref(false);
const submitting = ref(false);

const form = ref({
  asset_id: '', 
  consumable_id: '', 
  color: '' // Will be populated based on selection
});

// 1. Fetch the continuous cycle history for ALL printers
const fetchAllHistory = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/consumables/history'); 
    history.value = data.data || data;
  } finally {
    loading.value = false;
  }
};

// 2. Fetch the Inventory
const fetchInventory = async () => {
  const { data } = await axios.get('/api/consumables/list');
  // Store the full objects including colorStocks
  consumablesStock.value = (data.data || data).filter(c => ['Toner', 'Ink'].includes(c.category));
};

// 3. Fetch the Printers
const fetchPrinters = async () => {
  const { data } = await axios.get('/api/assets/list?category=Printer');
  printers.value = data.data || data;
};

const handleReplace = async () => {
  if (!form.value.asset_id || !form.value.consumable_id) {
    return alert('Please select both a Printer and a Replacement Cartridge.');
  }
  
  submitting.value = true;
  try {
    // This hits the route we added to AssetController
    await axios.post(`/api/assets/${form.value.asset_id}/replace-toner`, {
        consumable_id: form.value.consumable_id,
        color: form.value.color
    });
    
    alert('Inventory updated and toner cycle recorded!');
    form.value.consumable_id = ''; // Clear selection
    fetchAllHistory(); // Refresh the table
    fetchInventory(); // Refresh stock counts
  } catch (err) {
    alert(err.response?.data?.message || 'Error updating consumable');
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchAllHistory();
  fetchInventory();
  fetchPrinters();
});

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '---';
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Consumables / Toner Lifecycle</h1>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-indigo-100 mb-8">
      <h3 class="text-sm font-black text-indigo-700 uppercase mb-4 flex items-center gap-2">
        <i class="fa fa-sync"></i> Record Replacement / Usage
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Select Printer</label>
          <select v-model="form.asset_id" class="w-full border p-2 rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Which Printer?</option>
            <option v-for="p in printers" :key="p.id" :value="p.id">
              {{ p.Asset_Name || p.name }} ({{ p.Serial_No || p.serial }})
            </option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">New Cartridge Model</label>
          <select v-model="form.consumable_id" class="w-full border p-2 rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Select from Stock...</option>
            <option v-for="c in consumablesStock" :key="c.id" :value="c.id">
              {{ c.item_name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Select Color from Stock</label>
          <select v-model="form.color" class="w-full border p-2 rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none" :disabled="!form.consumable_id">
            <option value="">Which Color?</option>
            <template v-if="form.consumable_id">
              <option 
                v-for="cs in (consumablesStock.find(c => c.id === form.consumable_id)?.color_stocks || [])" 
                :key="cs.id" 
                :value="cs.color"
                :disabled="cs.in_stock <= 0"
              >
                {{ cs.color }} (Available: {{ cs.in_stock }})
              </option>
            </template>
          </select>
        </div>

        <div class="flex items-end">
          <button 
            @click="handleReplace" 
            :disabled="submitting || !form.asset_id"
            class="w-full bg-indigo-600 text-white py-2 rounded font-bold text-xs hover:bg-indigo-700 disabled:opacity-50 h-[38px] transition-all shadow-md"
          >
            {{ submitting ? 'Updating...' : 'RECORD REPLACEMENT' }}
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden shadow-md">
      <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Usage History & Active Cycles</h2>
        <button @click="fetchAllHistory" class="text-indigo-600 hover:text-indigo-800">
          <i class="fa fa-refresh" :class="{'fa-spin': loading}"></i>
        </button>
      </div>
      <table class="w-full text-left text-sm border-collapse">
        <thead class="bg-white text-gray-400 font-bold uppercase text-[10px] border-b">
          <tr>
            <th class="p-4">Printer (Asset)</th>
            <th class="p-4">Color</th>
            <th class="p-4">Ink Model Used</th>
            <th class="p-4">Started</th>
            <th class="p-4">Finished</th>
            <th class="p-4 text-center">Current Status</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-if="loading"><td colspan="6" class="p-10 text-center text-gray-400 italic">Fetching cycle data...</td></tr>
          
          <tr v-for="log in history" :key="log.id" :class="!log.depleted_at ? 'bg-green-50/50' : 'bg-white'">
            <td class="p-4">
              <div class="font-bold text-gray-700">{{ log.asset?.Asset_Name || log.asset?.name || 'Unknown Asset' }}</div>
              <div class="text-[10px] text-gray-400 font-mono">{{ log.asset?.Serial_No || log.asset?.serial || 'N/A' }}</div>
            </td>
            <td class="p-4">
              <span class="flex items-center gap-2 font-bold uppercase text-xs">
                 <div :class="{
                   'bg-black': (log.color || '').toLowerCase() === 'black',
                   'bg-cyan-400': (log.color || '').toLowerCase() === 'cyan',
                   'bg-pink-500': (log.color || '').toLowerCase() === 'magenta',
                   'bg-yellow-400': (log.color || '').toLowerCase() === 'yellow',
                   'bg-gray-300': !['black','cyan','magenta','yellow'].includes((log.color || '').toLowerCase())
                 }" class="w-3 h-3 rounded-full border border-gray-200 shadow-sm"></div>
                 {{ log.color || 'Unknown' }}
              </span>
            </td>
            <td class="p-4 text-gray-600 font-medium">
              {{ log.consumable?.item_name || log.consumable?.name || 'Generic Cartridge' }}
            </td>
            <td class="p-4 text-gray-500">
              {{ formatDate(log.installed_at) }}
            </td>
            <td class="p-4 text-gray-500">
              <span v-if="log.depleted_at">{{ formatDate(log.depleted_at) }}</span>
              <span v-else class="italic text-indigo-400">In Use</span>
            </td>
            <td class="p-4 text-center">
              <span v-if="!log.depleted_at" class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-tighter animate-pulse">
                Running
              </span>
              <span v-else class="px-3 py-1 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold uppercase tracking-tighter">
                Empty
              </span>
            </td>
          </tr>

          <tr v-if="history.length === 0 && !loading">
            <td colspan="6" class="p-12 text-center text-gray-400 italic">No usage history found. Use the form above to record the first toner installation.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>