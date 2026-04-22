<template>
  <div class="space-y-10">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">Inventory <span class="text-vilcom-blue">Hub</span></h1>
      <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Central nexus for all hardware, components, and enterprise software licenses.</p>
    </div>

    <!-- Hub Navigation Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <router-link 
        v-for="item in hubItems" 
        :key="item.name"
        :to="{ name: item.routeName }"
        :class="[
          'group relative p-10 rounded-[2.5rem] shadow-sm transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 overflow-hidden flex flex-col justify-between min-h-[280px]',
          item.colorClass
        ]"
      >
        <!-- Decor -->
        <div class="absolute -right-6 -top-6 size-32 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>

        <div class="relative z-10">
          <div class="p-4 bg-white/20 rounded-2xl w-fit backdrop-blur-md mb-6 shadow-lg group-hover:rotate-6 transition-all duration-500">
            <component :is="item.icon" class="size-10 text-white" />
          </div>
          <h3 class="font-black text-white text-2xl tracking-tight mb-2 uppercase">{{ item.name }}</h3>
          <p class="text-[10px] font-black text-white/60 uppercase tracking-[0.2em] leading-relaxed">{{ item.description }}</p>
        </div>

        <div class="relative z-10 flex items-center gap-2 text-white/40 group-hover:text-white transition-colors text-[10px] font-black uppercase tracking-widest mt-6">
          <span>Launch Module</span>
          <ChevronRight class="size-4 group-hover:translate-x-1 transition-transform" />
        </div>
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
import { Barcode, HardDrive, Save, Keyboard, ChevronRight } from 'lucide-vue-next';
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
