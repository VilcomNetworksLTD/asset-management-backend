<template>
  <div class="space-y-12">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">Directory <span class="text-vilcom-blue">& Vendors</span></h1>
      <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-[0.2em] leading-relaxed">System architecture, personnel management, and enterprise supply chain control.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
      <!-- People Card -->
      <router-link 
        :to="{ name: 'people-list' }"
        class="bg-white p-12 rounded-[3rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden"
      >
        <div class="absolute -right-10 -bottom-10 size-40 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-700 opacity-50"></div>
        
        <div class="flex items-start justify-between mb-8 relative z-10">
          <div class="p-5 bg-blue-50 rounded-2xl text-vilcom-blue shadow-lg shadow-blue-900/5 group-hover:bg-vilcom-blue group-hover:text-white transition-all duration-500 group-hover:rotate-6">
            <Users class="size-10" />
          </div>
          <div class="p-2 rounded-full bg-gray-50 group-hover:bg-vilcom-blue group-hover:text-white transition-colors duration-300">
            <ChevronRight class="size-6" />
          </div>
        </div>
        <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tight group-hover:text-vilcom-blue transition-colors relative z-10">Personnel & Staff</h3>
        <p class="text-sm text-gray-500 leading-relaxed font-medium relative z-10">
          Orchestrate system users, department structures, and administrative hierarchies. Control role-based access and organizational flow.
        </p>
      </router-link>

      <!-- Suppliers Card -->
      <router-link 
        :to="{ name: 'suppliers-list' }"
        class="bg-white p-12 rounded-[3rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden"
      >
        <div class="absolute -right-10 -bottom-10 size-40 bg-orange-50 rounded-full group-hover:scale-150 transition-transform duration-700 opacity-50"></div>

        <div class="flex items-start justify-between mb-8 relative z-10">
          <div class="p-5 bg-orange-50 rounded-2xl text-vilcom-orange shadow-lg shadow-orange-900/5 group-hover:bg-vilcom-orange group-hover:text-white transition-all duration-500 group-hover:rotate-6">
            <Truck class="size-10" />
          </div>
          <div class="p-2 rounded-full bg-gray-50 group-hover:bg-vilcom-orange group-hover:text-white transition-colors duration-300">
            <ChevronRight class="size-6" />
          </div>
        </div>
        <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tight group-hover:text-vilcom-orange transition-colors relative z-10">Suppliers & Logistics</h3>
        <p class="text-sm text-gray-500 leading-relaxed font-medium relative z-10">
          Manage international procurement channels, local vendors, and historical acquisition records for high-fidelity inventory auditing.
        </p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Users, Truck, ChevronRight } from 'lucide-vue-next';
</script>
