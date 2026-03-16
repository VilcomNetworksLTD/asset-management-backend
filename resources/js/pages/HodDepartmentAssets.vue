<template>
  <div class="p-8 bg-[#f8fafc] min-h-screen font-sans">
    <div class="max-w-7xl mx-auto mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 tracking-tight">
            Department <span class="text-indigo-500">Assets</span>
          </h1>
          <p class="text-slate-500 mt-1">View assets assigned to staff in your department.</p>
        </div>
        <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-200">
          <i class="fa fa-layer-group text-slate-800 text-xl"></i>
        </div>
      </div>
    </div>

    <div v-if="loading">
      <Loader />
    </div>

    <div v-if="error" class="max-w-3xl mx-auto bg-red-50 border-l-4 border-red-500 p-6 rounded-xl shadow-md flex items-center">
      <i class="fa fa-exclamation-triangle text-red-500 mr-4 text-2xl"></i>
      <p class="text-red-700 font-bold">{{ error }}</p>
    </div>

    <div v-if="!loading && !error" class="max-w-7xl mx-auto space-y-4">
      <div v-for="staff in staffWithAssets" :key="staff.id" 
           class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all">
        
        <div @click="toggleStaff(staff.id)" 
             class="flex justify-between items-center p-6 cursor-pointer hover:bg-slate-50 transition-colors"
             :class="{'border-b border-slate-100 bg-slate-50/50': expandedStaff.includes(staff.id)}">
          <div class="flex items-center space-x-5">
            <div class="h-12 w-12 rounded-xl bg-[#1e3a8a] flex items-center justify-center text-white text-xl shadow-lg">
              <i class="fa fa-user-tie"></i>
            </div>
            <div>
              <h2 class="font-bold text-slate-800 text-xl">{{ staff.name }}</h2>
              <div class="flex items-center space-x-2 mt-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-[#fff7ed] text-[#c2410c] border border-[#ffedd5]">
                  {{ staff.assets?.length || 0 }} ASSETS
                </span>
              </div>
            </div>
          </div>
          <i :class="['fa', 'text-slate-400 transition-transform duration-300', expandedStaff.includes(staff.id) ? 'fa-chevron-down rotate-180 text-slate-800' : 'fa-chevron-right']"></i>
        </div>

        <div v-if="expandedStaff.includes(staff.id)" class="p-6 bg-[#fcfcfc]">
          <div v-if="staff.assets?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="asset in staff.assets" :key="asset.id"
                 @click="showAssetDetails(asset, staff)"
                 class="bg-white p-5 rounded-2xl border border-slate-200 hover:border-[#f97316] hover:shadow-xl cursor-pointer transition-all group relative overflow-hidden">
              <div class="absolute top-0 right-0 h-1 w-0 group-hover:w-full bg-[#f97316] transition-all duration-300"></div>
              
              <div class="mb-4 overflow-hidden rounded-lg h-32 bg-slate-100">
                <img :src="asset.image_url || getAssetImage(asset.Asset_Category)" 
                     class="w-full h-full object-cover transition-transform group-hover:scale-110" />
              </div>

              <div class="flex items-start justify-between">
                <div>
                  <h3 class="font-black text-slate-800 text-lg leading-tight group-hover:text-indigo-500 transition-colors">{{ asset.Asset_Name }}</h3>
                  <p class="text-[10px] font-mono text-slate-400 mt-1 uppercase tracking-tighter">S/N: {{ asset.Serial_No || 'N/A' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedAsset" class="fixed inset-0 bg-[#f8fafc] z-50 overflow-y-auto animate-in fade-in duration-300">
      <div class="max-w-7xl mx-auto p-8">
        <div class="flex justify-end mb-6">
          <button @click="selectedAsset = null" class="bg-[#1e3a8a] text-white px-6 py-2.5 rounded-full font-bold hover:bg-[#f97316] transition-colors shadow-lg flex items-center">
            <i class="fa fa-arrow-left mr-2"></i> BACK TO LIST
          </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mb-8 flex flex-col lg:flex-row relative">
          <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#f97316]"></div>
          
          <div class="p-10 lg:w-1/3 flex items-center justify-center bg-slate-50/30 border-r border-slate-100 min-h-[300px]">
            <img :src="selectedAsset.image_url || getAssetImage(selectedAsset.Asset_Category)" 
                 class="max-w-full h-auto max-h-64 object-contain drop-shadow-2xl rounded-2xl shadow-xl" 
                 alt="Asset Image">
          </div>

          <div class="p-10 flex-grow">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8">
              <div>
                <h2 class="text-5xl font-black text-slate-800 tracking-tight">{{ selectedAsset.Asset_Name }}</h2>
                <div class="flex items-center space-x-3 mt-3">
                  <span class="text-indigo-500 font-black text-xs uppercase tracking-widest italic">TAG: {{ selectedAsset.Asset_Tag || 'NO TAG' }}</span>
                  <span class="text-slate-300">|</span>
                  <span class="text-indigo-500 font-black text-xs uppercase tracking-widest italic">S/N: {{ selectedAsset.Serial_No }}</span>
                </div>
              </div>
              <span class="px-5 py-2 rounded-full bg-blue-50 text-slate-800 font-black text-xs uppercase tracking-widest border border-blue-100">
                {{ selectedAsset.status?.Status_Name || 'READY TO DEPLOY' }}
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 pt-8 border-t border-slate-100">
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">CATEGORY</p>
                <p class="text-slate-800 font-bold text-lg">{{ selectedAsset.Asset_Category || 'Desktop' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">CURRENT USER</p>
                <p class="text-slate-800 font-bold text-lg">{{ selectedAsset.current_user || 'Unassigned' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">LOCATION</p>
                <p class="text-slate-800 font-bold text-lg">{{ selectedAsset.location || 'Mombasa Office' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden border-t-4 border-t-[#2563eb]">
            <div class="p-6 border-b border-slate-100 flex items-center space-x-3">
              <div class="bg-[#2563eb] p-2 rounded-lg text-white text-sm"><i class="fa fa-cog"></i></div>
              <h4 class="font-black text-slate-800 text-xs uppercase tracking-widest">SYSTEM CONFIGURATION</h4>
            </div>
            <div class="p-8 grid grid-cols-2 gap-y-8 gap-x-4 text-slate-700">
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">PROCESSOR</p>
                <p class="font-bold">{{ selectedAsset.specs?.processor || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">MEMORY</p>
                <p class="font-bold">{{ selectedAsset.specs?.memory || 'N/A' }}</p>
              </div>
              </div>
          </div>
          
          <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden border-t-4 border-t-[#f97316]">
            <div class="p-6 border-b border-slate-100 flex items-center space-x-3">
              <div class="bg-[#f97316] p-2 rounded-lg text-white text-sm"><i class="fa fa-wallet"></i></div>
              <h4 class="font-black text-slate-800 text-xs uppercase tracking-widest">FINANCIALS</h4>
            </div>
            <div class="p-8 grid grid-cols-2 gap-y-8 gap-x-4">
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">PURCHASE PRICE</p>
                <p class="text-slate-800 font-black text-lg">{{ formatMoney(selectedAsset.purchase_price) }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">CURRENT VALUE</p>
                <p class="text-indigo-500 font-black text-lg">{{ formatMoney(selectedAsset.current_value) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="bg-[#1e3a8a] p-6 text-white"><h4 class="font-black text-xs uppercase tracking-widest">ASSIGNMENT HISTORY LOG</h4></div>
          <div v-if="selectedAsset.history && selectedAsset.history.length > 0" class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-blue-50/50 border-b border-slate-100">
                <tr>
                  <th class="px-8 py-4 text-[10px] font-black text-slate-800 uppercase tracking-widest">USER NAME</th>
                  <th class="px-8 py-4 text-[10px] font-black text-slate-800 uppercase tracking-widest">DATE ASSIGNED</th>
                  <th class="px-8 py-4 text-[10px] font-black text-slate-800 uppercase tracking-widest">DATE RETURNED</th>
                  <th class="px-8 py-4 text-[10px] font-black text-slate-800 uppercase tracking-widest">NOTES</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in selectedAsset.history" :key="entry.id" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                  <td class="px-8 py-4 text-slate-700 font-bold">{{ entry.user_name }}</td>
                  <td class="px-8 py-4 text-slate-500 font-mono text-sm">{{ entry.date }}</td>
                  <td class="px-8 py-4 text-slate-500 font-mono text-sm">{{ entry.date_returned || '---' }}</td>
                  <td class="px-8 py-4 text-slate-500 text-sm italic">{{ entry.notes || '---' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-8 text-center text-slate-400 italic">
            No assignment history available.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useSettings } from '../composables/useSettings';

const staffWithAssets = ref([]);
const loading = ref(true);
const error = ref(null);
const expandedStaff = ref([]);
const selectedAsset = ref(null);

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '0.00';
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
  return 'https://images.unsplash.com/photo-1586769852836-bc069f19e1b6?q=80&w=500';
};

const fetchDepartmentAssets = async () => {
  try {
    const response = await axios.get('/api/hod/department-assets');
    staffWithAssets.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load assets.';
  } finally {
    loading.value = false;
  }
};

const toggleStaff = (staffId) => {
  const index = expandedStaff.value.indexOf(staffId);
  index > -1 ? expandedStaff.value.splice(index, 1) : expandedStaff.value.push(staffId);
};

const showAssetDetails = async (asset, staff) => {
  console.log("FUNCTION TRIGGERED");
  
  selectedAsset.value = {
    ...asset,
    current_user: staff.name,
    history: []
  };

  try {
   
    const response = await axios.get(`/api/assets/${asset.id}`);
    console.log("SUCCESS:", response.data);
   
    selectedAsset.value = { ...selectedAsset.value, ...response.data, current_user: staff.name };
  } catch (err) {
    console.error("ERROR:", err);
  }
};

onMounted(fetchDepartmentAssets);
</script>