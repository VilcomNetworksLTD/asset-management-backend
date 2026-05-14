<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useWindowFocus } from '@vueuse/core';
import Loader from '@/components/Loader.vue';
import { RefreshCcw, Printer, Droplets, Clock, CheckCircle2, XCircle, Search, ChevronLeft, ChevronRight, Plus, Activity } from 'lucide-vue-next';


const isFocused = useWindowFocus()
const REFRESH_INTERVAL = 30000
let intervalId = null

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
  intervalId = setInterval(() => {
    fetchAllHistory();
    fetchInventory();
  }, REFRESH_INTERVAL);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '---';
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Consumable <span class="text-vilcom-blue">Lifecycle</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Monitoring Ink & Toner Depletion Rates
        </p>
      </div>
    </div>

    <!-- Action Card -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-10 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-vilcom-orange text-white rounded-2xl shadow-lg shadow-orange-900/10">
             <Droplets class="size-6" />
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-800 tracking-tight">Record Replacement</h3>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Update Cartridge Status</p>
          </div>
        </div>
      </div>

      <div class="p-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Target Printer</label>
            <select v-model="form.asset_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all cursor-pointer">
              <option value="">Select Device...</option>
              <option v-for="p in printers" :key="p.id" :value="p.id">
                {{ p.Asset_Name || p.name }}
              </option>
            </select>
          </div>

          <div class="space-y-2 md:col-span-1">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Cartridge Unit</label>
            <select v-model="form.consumable_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all cursor-pointer">
              <option value="">Select Stock...</option>
              <option v-for="c in consumablesStock" :key="c.id" :value="c.id">
                {{ c.item_name }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Color Variant</label>
            <select v-model="form.color" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all cursor-pointer" :disabled="!form.consumable_id">
              <option value="">Select Color...</option>
              <template v-if="form.consumable_id">
                <option 
                  v-for="cs in (consumablesStock.find(c => c.id === form.consumable_id)?.color_stocks || [])" 
                  :key="cs.id" 
                  :value="cs.color"
                  :disabled="cs.in_stock <= 0"
                >
                  {{ cs.color }} ({{ cs.in_stock }} units left)
                </option>
              </template>
            </select>
          </div>

          <div class="flex items-end">
            <button 
              @click="handleReplace" 
              :disabled="submitting || !form.asset_id || !form.color"
              class="w-full py-4 bg-vilcom-blue text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/10 hover:scale-105 active:scale-95 transition-all disabled:opacity-30"
            >
              {{ submitting ? 'Updating...' : 'Certify Replacement' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Usage History -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
        <h2 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
          <Activity class="size-4 text-vilcom-blue" />
          Active Cycles & Depletion History
        </h2>
        <button @click="fetchAllHistory" class="p-3 bg-white border border-gray-100 rounded-xl text-slate-400 hover:text-vilcom-blue hover:border-vilcom-blue transition-all">
          <RefreshCcw class="size-4" :class="{'animate-spin': loading}" />
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Printer Instance</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Color Profile</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Consumable Model</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Timeline</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Scanning usage logs...
              </td>
            </tr>
            <tr v-for="log in history" :key="log.id" :class="!log.depleted_at ? 'bg-blue-50/30 group' : 'bg-white group'" class="hover:bg-slate-50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Printer class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ log.asset?.Asset_Name || log.asset?.name || 'Independent Unit' }}
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono">
                      {{ log.asset?.Serial_No || log.asset?.serial || 'SER-ID-UNKNOWN' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div :class="{
                    'bg-slate-900 shadow-lg shadow-black/20': (log.color || '').toLowerCase() === 'black',
                    'bg-cyan-400 shadow-lg shadow-cyan-400/20': (log.color || '').toLowerCase() === 'cyan',
                    'bg-pink-500 shadow-lg shadow-pink-500/20': (log.color || '').toLowerCase() === 'magenta',
                    'bg-yellow-400 shadow-lg shadow-yellow-400/20': (log.color || '').toLowerCase() === 'yellow',
                    'bg-gray-300': !['black','cyan','magenta','yellow'].includes((log.color || '').toLowerCase())
                  }" class="size-4 rounded-full border border-white/50"></div>
                  <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">{{ log.color || 'Unknown' }}</span>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="text-xs font-black text-slate-700">{{ log.consumable?.item_name || 'Generic Cartridge' }}</div>
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-col gap-1">
                  <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                    <span class="text-teal-600">IN:</span> {{ formatDate(log.installed_at) }}
                  </div>
                  <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                    <span class="text-vilcom-orange">OUT:</span> {{ log.depleted_at ? formatDate(log.depleted_at) : '---' }}
                  </div>
                </div>
              </td>
              <td class="px-8 py-5 text-center">
                <span v-if="!log.depleted_at" class="px-4 py-1.5 rounded-xl bg-teal-50 text-teal-600 text-[9px] font-black uppercase tracking-widest ring-1 ring-teal-100 flex items-center justify-center gap-2">
                  <span class="size-1.5 bg-teal-500 rounded-full animate-pulse"></span>
                  Active Cycle
                </span>
                <span v-else class="px-4 py-1.5 rounded-xl bg-gray-100 text-gray-400 text-[9px] font-black uppercase tracking-widest flex items-center justify-center gap-2">
                  Depleted
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>