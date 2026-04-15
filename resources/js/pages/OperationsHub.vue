<template>
  <div class="space-y-12">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">System <span class="text-vilcom-blue">Operations</span></h1>
      <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-[0.2em] leading-relaxed">Critical infra, system health, audit trails, and global compliance monitoring.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <router-link 
        v-for="item in hubItems" 
        :key="item.name"
        :to="{ name: item.routeName }"
        class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 group overflow-hidden relative"
      >
        <div class="absolute -right-4 -top-4 size-20 bg-gray-50 rounded-full group-hover:scale-150 transition-transform duration-700 opacity-50"></div>

        <div class="flex items-center gap-6 mb-6 relative z-10">
          <div :class="['p-4 rounded-2xl bg-gray-50 group-hover:text-white shadow-sm transition-all duration-500 group-hover:rotate-6 group-hover:scale-110', item.colorClass]">
            <component :is="item.icon" class="size-7" />
          </div>
          <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ item.name }}</h3>
        </div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest line-clamp-2 px-1 relative z-10">{{ item.description }}</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Wrench, History, FileText, Lock, MessageSquare, Ticket, ShoppingCart, Send, Droplet, Package } from 'lucide-vue-next';

const hubItems = [
  { 
    name: 'Tickets', 
    routeName: 'admin-tickets', 
    icon: Ticket, 
    colorClass: 'text-purple-600 group-hover:bg-purple-600',
    description: 'Track and manage reported issues and support requests.'
  },
  {  
    name: 'Maintenances', 
    routeName: 'maintenances-list', 
    icon: Wrench, 
    colorClass: 'text-orange-600 group-hover:bg-orange-600',
    description: 'Track repairs, scheduling, and asset health history.'
  },
  { 
    name: 'Purchase Escalations', 
    routeName: 'admin-purchase-requests', 
    icon: Send, 
    colorClass: 'text-vilcom-orange group-hover:bg-vilcom-orange',
    description: 'Review staff requests and escalate to management for approval.'
  },
  { 
    name: 'Activity Logs', 
    routeName: 'activitylogs-list', 
    icon: History, 
    colorClass: 'text-blue-600 group-hover:bg-blue-600',
    description: 'Complete audit trail of system actions and changes.'
  },
  { 
    name: 'Reports', 
    routeName: 'reports-list', 
    icon: FileText, 
    colorClass: 'text-teal-600 group-hover:bg-teal-600',
    description: 'Export inventory data, deployment stats, and insights.'
  },
  { 
    name: 'SSL Certificates', 
    routeName: 'ssl-certificates', 
    icon: Lock, 
    colorClass: 'text-indigo-600 group-hover:bg-indigo-600',
    description: 'Monitor domain security and certificate expiration dates.'
  },
  { 
    name: 'Feedback', 
    routeName: 'admin-feedback', 
    icon: MessageSquare, 
    colorClass: 'text-pink-600 group-hover:bg-pink-600',
    description: 'User suggestions and experience feedback.'
  },
  { 
    name: 'Toner Lifecycle', 
    routeName: 'consumables-list', 
    icon: Droplet, 
    colorClass: 'text-cyan-600 group-hover:bg-cyan-600',
    description: 'Manage toner and ink consumable lifecycle and requests.'
  },
  { 
    name: 'Toner Inventory', 
    routeName: 'toner-inventory', 
    icon: Package, 
    colorClass: 'text-emerald-600 group-hover:bg-emerald-600',
    description: 'Track toner stock levels, colors, and inventory alerts.'
  }
];
</script>
