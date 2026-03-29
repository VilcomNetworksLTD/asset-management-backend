<template>
  <div class="p-8 space-y-10">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">Inventory <span class="text-vilcom-blue">Hub</span></h1>
      <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Central nexus for all hardware, components, and enterprise software licenses.</p>
    </div>

    <!-- Hub Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <router-link 
        v-for="item in hubItems" 
        :key="item.name"
        :to="{ name: item.routeName }"
        class="group relative bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 flex flex-col items-center text-center overflow-hidden"
      >
        <!-- Decor -->
        <div class="absolute -right-4 -top-4 size-20 bg-gray-50 rounded-full group-hover:scale-150 transition-transform duration-700"></div>

        <div :class="['relative z-10 p-5 rounded-2xl mb-6 shadow-lg group-hover:rotate-6 transition-all duration-300', item.colorClass]">
          <component :is="item.icon" class="size-8 text-white" />
        </div>
        <h3 class="relative z-10 font-black text-slate-800 text-lg group-hover:text-vilcom-blue transition-colors">{{ item.name }}</h3>
        <p class="relative z-10 text-xs font-bold text-gray-400 uppercase tracking-widest mt-2 px-4">{{ item.description }}</p>
      </router-link>
    </div>

    <!-- Global Asset Pulse -->
    <div class="bg-white rounded-[3rem] p-12 border border-gray-100 shadow-sm relative overflow-hidden group">
      <div class="absolute right-0 top-0 h-full w-1/3 bg-slate-50 opacity-0 group-hover:opacity-100 transition-opacity duration-1000 -skew-x-12 translate-x-1/2"></div>
      
      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-12">
        <div class="max-w-md">
           <div class="flex items-center gap-3 mb-4">
             <div class="size-2 rounded-full bg-green-500 animate-pulse"></div>
             <span class="text-[10px] font-black text-vilcom-blue uppercase tracking-[0.2em]">Real-time Inventory Pulse</span>
           </div>
           <h2 class="text-2xl font-black text-slate-800 tracking-tight mb-4">Inventory Health Overview</h2>
           <p class="text-sm text-gray-500 leading-relaxed font-medium">Monitoring global asset distribution and stock replenishment requirements in real-time.</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-12 lg:gap-20">
          <div>
            <div class="text-4xl font-black text-vilcom-blue mb-2 tracking-tighter">{{ stats.totalAssets }}</div>
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Assets</div>
          </div>
          <div>
            <div class="text-4xl font-black text-vilcom-orange mb-2 tracking-tighter">{{ stats.lowStock }}</div>
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">Critical Stock</div>
          </div>
          <div>
            <div class="text-4xl font-black text-teal-600 mb-2 tracking-tighter">{{ stats.deployed }}</div>
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">Active Deployments</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Barcode, HardDrive, Save, Keyboard } from 'lucide-vue-next';
import axios from 'axios';

const hubItems = [
  { 
    name: 'Assets', 
    routeName: 'assets-list', 
    icon: Barcode, 
    colorClass: 'bg-vilcom-blue',
    description: 'Hardware, laptops, and equipment'
  },
  { 
    name: 'Components', 
    routeName: 'components-list', 
    icon: HardDrive, 
    colorClass: 'bg-indigo-500',
    description: 'RAM, SSDs, and internal parts'
  },
  { 
    name: 'Licenses', 
    routeName: 'licenses-list', 
    icon: Save, 
    colorClass: 'bg-teal-500',
    description: 'Software keys and subscriptions'
  },
  { 
    name: 'Accessories', 
    routeName: 'accessories-list', 
    icon: Keyboard, 
    colorClass: 'bg-vilcom-orange',
    description: 'Peripherals and small items'
  }
];

const stats = ref({
  totalAssets: 0,
  lowStock: 0,
  deployed: 0
});

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/stats');
    stats.value = {
      totalAssets: data.total_assets || 0,
      lowStock: data.low_stock_count || 0,
      deployed: data.deployed_count || 0
    };
  } catch (error) {
    console.error("Failed to fetch hub stats", error);
  }
});
</script>
