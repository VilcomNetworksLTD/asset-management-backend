<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Personal <span class="text-vilcom-blue">Accessories</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Assigned Peripheral Inventory
        </p>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Accessory Profile</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Classification</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Volume</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Acquisition Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="4" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Accessing vault...
              </td>
            </tr>
            <tr v-for="accessory in accessories" :key="accessory.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Package class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ accessory.name }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">
                  {{ accessory.category || 'Standard' }}
                </span>
              </td>
              <td class="px-6 py-5">
                <div class="font-black text-slate-700 text-sm">
                  {{ accessory.pivot.quantity }} <span class="text-[9px] text-gray-400 uppercase tracking-widest ml-1">Units</span>
                </div>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center justify-end gap-2">
                  <Clock class="size-3 text-vilcom-blue" />
                  {{ formatDate(accessory.pivot.created_at) }}
                </div>
              </td>
            </tr>
            <tr v-if="!loading && accessories.length === 0">
              <td colspan="4" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                No peripherals assigned to your profile.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Package, Clock, ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';


const accessories = ref([]);
const loading = ref(true);

const fetchMyAccessories = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/my-accessories');
    accessories.value = data;
  } catch (error) {
    console.error("Failed to fetch user's accessories:", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

onMounted(fetchMyAccessories);
</script>
