<template>
  <aside 
    :class="[
      'fixed top-[50px] left-0 bottom-0 z-[810] bg-[#222d32] overflow-y-auto transition-transform duration-300 ease-in-out',
      isCollapsed ? '-translate-x-full w-0' : 'translate-x-0 w-[230px]'
    ]"
  >
    <section class="pb-[30px]">
      <div class="flex items-center p-[10px] bg-[#1a2226]">
        <div class="flex-shrink-0">
          <img src="https://i.pravatar.cc/150?img=11" class="w-[45px] h-[45px] rounded-full mr-[10px]" alt="User Image">
        </div>
        <div class="leading-tight">
          <p class="text-white font-semibold mb-[5px]">{{ userName }}</p>
          <a href="#" class="text-[#b8c7ce] text-[11px] no-underline">
            <i class="fa fa-circle text-[#3c763d]"></i> Online
          </a>
        </div>
      </div>

      <ul class="list-none p-0 m-0">
        <li class="text-[#4b646f] bg-[#1a2226] px-[15px] py-[10px] text-[12px] tracking-wider uppercase font-bold">
          {{ userRole === 'admin' ? 'MAIN NAVIGATION' : 'STAFF NAVIGATION' }}
        </li>
        
        <li v-for="item in filteredMenuItems" :key="item.label">
          <router-link 
            :to="item.path" 
            class="flex items-center px-[15px] py-[12px] no-underline border-l-[3px] transition-colors duration-200 text-[#b8c7ce] border-transparent hover:text-white hover:bg-[#1e282c] hover:border-[#3c8dbc]"
            active-class="text-white bg-[#1e282c] border-[#3c8dbc]!"
          >
            <i :class="[item.icon, 'w-[20px] mr-[5px]']"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </section>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

defineProps({
  isCollapsed: Boolean
});

const userRole = ref('user'); // Default to user
const userName = ref('User');

onMounted(() => {
  const userData = localStorage.getItem('user_data');
  if (userData) {
    const user = JSON.parse(userData);
    userName.value = user.name || 'User';
    // CRITICAL: Ensure this matches the role key in your database/localStorage
    userRole.value = user.role ? user.role.toLowerCase() : 'user'; 
  }
});

const menuItems = [
  // ADMIN SECTION
  { label: 'Dashboard', icon: 'fa fa-tachometer-alt', path: '/dashboard/admin', roles: ['admin'] },
  { label: 'Assets', icon: 'fa fa-barcode', path: '/assets', roles: ['admin'] },
  { label: 'Components', icon: 'fa fa-hdd', path: '/components', roles: ['admin'] },
  { label: 'Licenses', icon: 'fa fa-save', path: '/licenses', roles: ['admin'] },
  { label: 'Accessories', icon: 'fa fa-keyboard', path: '/accessories', roles: ['admin'] },
  { label: 'Consumables', icon: 'fa fa-tint', path: '/consumables', roles: ['admin'] },
  { label: 'People', icon: 'fa fa-users', path: '/people', roles: ['admin'] },
  { label: 'Maintenances', icon: 'fa fa-wrench', path: '/maintenances', roles: ['admin'] }, 
  { label: 'Activity Logs', icon: 'fa fa-history', path: '/logs', roles: ['admin'] },       
  { label: 'Transfer Assets', icon: 'fa fa-exchange-alt', path: '/transfers/assets', roles: ['admin'] },
  { label: 'Return Assets', icon: 'fa fa-undo', path: '/transfers/returns', roles: ['admin'] },
  { label: 'User Issues', icon: 'fa fa-exclamation-circle', path: '/tickets', roles: ['admin'] },
  { label: 'Reports', icon: 'fa fa-file-alt', path: '/reports', roles: ['admin'] },       
  { label: 'Settings', icon: 'fa fa-cogs', path: '/settings', roles: ['admin'] },

  // USER SECTION - FIXED PATHS
  { label: 'My Dashboard', icon: 'fa fa-home', path: '/dashboard/user', roles: ['user'] },
  { label: 'My Equipment', icon: 'fa fa-laptop', path: '/my-assets', roles: ['user'] }, 
  { label: 'Report an Issue', icon: 'fa fa-plus-circle', path: '/report-issue', roles: ['user'] },
  { label: 'Support Tickets', icon: 'fa fa-ticket', path: '/my-tickets', roles: ['user'] },
  { label: 'Request Transfer', icon: 'fa fa-exchange-alt', path: '/request-transfer', roles: ['user'] }, 
  { label: 'Request Return', icon: 'fa fa-undo', path: '/request-return', roles: ['user'] },
  { label: 'My Profile', icon: 'fa fa-user', path: '/profile', roles: ['user'] },
  { label: 'Settings', icon: 'fa fa-cog', path: '/user/settings', roles: ['user'] },        
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});
</script>