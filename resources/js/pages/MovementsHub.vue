<template>
  <div class="space-y-10">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">Movements <span class="text-vilcom-orange">Hub</span></h1>
      <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed text-wrap">Streamlined tracking of asset transfers and returns across the enterprise ecosystem.</p>
    </div>

    <!-- Hub Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
      <router-link 
        v-for="item in hubItems" 
        :key="item.name"
        :to="{ name: item.routeName }"
        :class="[
          'group relative p-12 rounded-[3.5rem] shadow-sm transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 overflow-hidden flex items-center gap-10 min-h-[240px]',
          item.colorClass
        ]"
      >
        <!-- Decor -->
        <div class="absolute -right-10 -bottom-10 size-60 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>

        <div class="relative z-10 p-6 rounded-3xl bg-white/20 shadow-xl backdrop-blur-md transition-all duration-500 group-hover:rotate-12 group-hover:scale-110">
          <component :is="item.icon" class="size-12 text-white" />
        </div>
        <div class="relative z-10">
          <h3 class="text-3xl font-black text-white mb-2 uppercase tracking-tight">{{ item.name }}</h3>
          <p class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em] leading-relaxed">{{ item.description }}</p>
          
          <div class="flex items-center gap-2 text-white/40 group-hover:text-white transition-colors text-[10px] font-black uppercase tracking-widest mt-6">
            <span>Authorize Module</span>
            <ChevronRight class="size-4 group-hover:translate-x-1 transition-transform" />
          </div>
        </div>
      </router-link>
    </div>

    <!-- Info Section -->
    <div class="bg-slate-900 rounded-[3.5rem] p-12 text-white shadow-2xl relative overflow-hidden group">
      <div class="relative z-10 w-full md:w-3/5">
        <div class="flex items-center gap-3 mb-6">
           <div class="size-2 rounded-full bg-vilcom-orange shadow-[0_0_10px_#f97316]"></div>
           <span class="text-[10px] font-black text-vilcom-orange uppercase tracking-[0.2em]">Compliance Protocol</span>
        </div>
        <h2 class="text-3xl font-black mb-6 tracking-tight">Handover <span class="text-vilcom-orange">Verification</span></h2>
        <p class="text-slate-400 mb-10 leading-relaxed font-medium">
          Automated asset movement audits ensure that every handover is electronically signed and verified by all stakeholders, maintaining 100% data fidelity.
        </p>
        <div class="flex flex-wrap gap-4">
          <div class="bg-white/5 border border-white/10 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
            <span class="size-1.5 rounded-full bg-blue-500"></span> Blockchain Audit Trail
          </div>
          <div class="bg-white/5 border border-white/10 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
            <span class="size-1.5 rounded-full bg-orange-500"></span> Bi-Directional Signature
          </div>
        </div>
      </div>
      
      <!-- Background geometric shape -->
      <div class="absolute -right-20 -top-20 size-96 bg-vilcom-blue/20 rounded-full blur-[80px] group-hover:scale-110 transition-transform duration-[2s]"></div>
      <div class="absolute -left-10 -bottom-10 size-60 bg-vilcom-orange/10 rounded-full blur-[60px]"></div>
    </div>
  </div>
</template>

<script setup>
import { ArrowLeftRight, Undo2 } from 'lucide-vue-next';

const hubItems = [
  { 
    name: 'Asset Transfers', 
    routeName: 'transfer-assets-list', 
    icon: ArrowLeftRight, 
    colorClass: 'bg-vilcom-blue',
    description: 'Deploy assets to staff or move between users.'
  },
  { 
    name: 'Asset Returns', 
    routeName: 'return-assets-list', 
    icon: Undo2, 
    colorClass: 'bg-vilcom-orange',
    description: 'Process check-ins and inbound asset returns.'
  }
];
</script>
