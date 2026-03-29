<template>
  <div class="p-8 space-y-12">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">My <span class="text-vilcom-blue">Workspace</span></h1>
      <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-[0.2em] leading-relaxed">Your personal asset ledger, active support channels, and compliance verifications.</p>
    </div>

    <!-- Main Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <router-link 
        v-for="cat in categories" 
        :key="cat.name"
        :to="{ name: cat.routeName }"
        class="group bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 flex flex-col items-center text-center overflow-hidden relative"
      >
        <div class="absolute -right-4 -top-4 size-20 bg-gray-50 rounded-full group-hover:scale-150 transition-transform duration-700 opacity-50"></div>

        <div :class="['relative z-10 p-5 rounded-2xl mb-6 shadow-lg group-hover:rotate-6 transition-all duration-500', cat.colorClass]">
          <component :is="cat.icon" class="size-8" />
        </div>
        <div class="relative z-10">
          <div class="text-[10px] uppercase font-black text-gray-400 tracking-[0.2em] mb-1">{{ cat.label }}</div>
          <div class="text-lg font-black text-slate-800 group-hover:text-vilcom-blue transition-colors">{{ cat.name }}</div>
        </div>
      </router-link>
    </div>

    <!-- Secondary Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div v-for="sect in secondSection" :key="sect.name" class="bg-white rounded-[3rem] p-10 border border-gray-100 shadow-sm flex flex-col justify-between group hover:shadow-xl transition-all duration-500">
        <div>
          <div class="flex items-center gap-4 mb-6">
            <div :class="['p-4 rounded-2xl bg-gray-50 shadow-inner group-hover:rotate-6 transition-all duration-500', sect.iconColor]">
              <component :is="sect.icon" class="size-6" />
            </div>
            <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ sect.name }}</h3>
          </div>
          <p class="text-sm text-gray-500 mb-10 leading-relaxed font-medium">{{ sect.description }}</p>
        </div>
        <router-link :to="{ name: sect.routeName }" class="group/btn inline-flex items-center gap-3 text-vilcom-blue text-[10px] font-black uppercase tracking-[0.2em] hover:opacity-80 transition-all">
          <span class="px-6 py-3 bg-blue-50 rounded-xl group-hover/btn:bg-vilcom-blue group-hover/btn:text-white transition-all">Launch Module</span>
          <div class="p-3 bg-gray-50 rounded-xl group-hover:translate-x-2 transition-transform">
             <ChevronRight class="size-4" />
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { 
  Laptop, Save, HardDrive, Keyboard, 
  Ticket, LogIn, User, ChevronRight, ArrowLeftRight, ShoppingCart
} from 'lucide-vue-next';

// Get user role to conditionally show menu items
const user = JSON.parse(localStorage.getItem('user_data') || '{}');
const userRole = (user.role || '').toLowerCase();

const categories = [
  { name: 'Equipment', label: 'My', routeName: 'user-assets', icon: Laptop, colorClass: 'text-blue-600' },
  { name: 'Licenses', label: 'My', routeName: 'user-licenses', icon: Save, colorClass: 'text-teal-600' },
  { name: 'Components', label: 'My', routeName: 'user-components', icon: HardDrive, colorClass: 'text-indigo-600' },
  { name: 'Accessories', label: 'My', routeName: 'user-accessories', icon: Keyboard, colorClass: 'text-orange-600' },
];

const allSecondSection = [
  { 
    name: 'Asset Request', 
    icon: Laptop, 
    iconColor: 'text-indigo-600', 
    routeName: 'user.request-return', 
    description: 'Initiate a request for a new asset or equipment.' 
  },
  { 
    name: 'Transfer Request', 
    icon: ArrowLeftRight, 
    iconColor: 'text-teal-600', 
    routeName: 'user.request-transfer', 
    description: 'Transition an existing asset to another user or sector.' 
  },
  { 
    name: 'Purchase Escalations', 
    icon: ShoppingCart, 
    iconColor: 'text-vilcom-orange', 
    routeName: 'management-purchase-requests', 
    description: 'Review and authorize escalated asset purchase requests.',
    roles: ['management']
  },
  { 
    name: 'Support Tickets', 
    icon: Ticket, 
    iconColor: 'text-vilcom-blue', 
    routeName: 'user-tickets', 
    description: 'Track your reported issues and IT support requests.' 
  },
  { 
    name: 'Verifications', 
    icon: LogIn, 
    iconColor: 'text-vilcom-orange', 
    routeName: 'user.inbound-verifications', 
    description: 'Verify and accept new assets assigned to you.' 
  },
  { 
    name: 'My Profile', 
    icon: User, 
    iconColor: 'text-gray-600', 
    routeName: 'user.profile', 
    description: 'Update your contact information and security settings.' 
  },
];

// Filter out items based on user role
const secondSection = computed(() => {
  return allSecondSection.filter(item => {
    // If no roles specified, show to all users
    if (!item.roles) return true;
    // Otherwise, only show if user role matches
    return item.roles.includes(userRole);
  });
});
</script>
