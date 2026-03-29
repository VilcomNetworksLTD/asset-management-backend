<template>
  <div class="p-8 space-y-10">
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
        class="group bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 flex items-center gap-10 overflow-hidden relative"
      >
        <div class="absolute -right-10 -bottom-10 size-40 bg-gray-50 rounded-full group-hover:scale-150 transition-transform duration-700 opacity-50"></div>

        <div :class="['relative z-10 p-6 rounded-3xl shadow-xl transition-all duration-500 group-hover:rotate-12 group-hover:scale-110', item.colorClass]">
          <component :is="item.icon" class="size-12 text-white" />
        </div>
        <div class="relative z-10">
          <h3 class="text-2xl font-black text-slate-800 group-hover:text-vilcom-blue transition-colors">{{ item.name }}</h3>
          <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.15em] mt-2">{{ item.description }}</p>
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
