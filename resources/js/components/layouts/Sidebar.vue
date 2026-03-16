<template>
  <aside 
    :class="[
      'bg-[#222d32] h-screen overflow-y-auto transition-all duration-300 ease-in-out flex flex-col',
      collapsed ? 'w-20' : 'w-[230px]'
    ]"
  >
    <div v-if="!collapsed" class="flex items-center p-[10px] bg-[#1a2226] shrink-0">
      <div class="flex-shrink-0">
        <img src="https://i.pravatar.cc/150?img=11" class="w-[45px] h-[45px] rounded-full mr-[10px]" alt="User Image">
      </div>
      <div class="leading-tight">
        <p class="text-white font-semibold mb-[5px]">{{ userName }}</p>
        <div class="text-[#b8c7ce] text-[11px] flex items-center">
          <div class="w-2 h-2 rounded-full bg-[#3c763d] mr-1"></div> Online
        </div>
      </div>
    </div>

    <div v-else class="flex items-center justify-center p-[10px] bg-[#1a2226] h-[65px] shrink-0">
        <img src="https://i.pravatar.cc/150?img=11" class="w-[45px] h-[45px] rounded-full" alt="User Image">
    </div>

    <ul class="flex-1 flex flex-col list-none p-0 m-0">
      <li v-if="!collapsed" class="text-[#4b646f] bg-[#1a2226] px-[15px] py-[10px] text-[12px] tracking-wider uppercase font-bold shrink-0">
        {{ userRole === 'admin' ? 'MAIN NAVIGATION' : 'STAFF NAVIGATION' }}
      </li>
      
      <li 
        v-for="item in filteredMenuItems" 
        :key="item.label"
        :class="[
          'flex flex-col shrink-0',
          /* Dynamic push to bottom logic */
          (item.label === 'Settings' && userRole === 'admin') || (item.label === 'My Profile') ? 'mt-auto' : ''
        ]"
      >
        <router-link 
          :to="item.path" 
          class="group flex items-center px-[15px] py-[12px] no-underline border-l-[3px] transition-colors duration-200 text-[#b8c7ce] border-transparent hover:text-white hover:bg-[#1e282c] hover:border-[#3c8dbc]"
          active-class="text-white bg-[#1e282c] !border-[#3c8dbc]"
          :title="collapsed ? item.label : ''"
        >
          <div :class="['flex items-center justify-center shrink-0', collapsed ? 'w-full' : 'w-[20px] mr-[10px]']">
            <component :is="item.icon" :class="[collapsed ? 'size-6' : 'size-4']" />
          </div>
          <span v-if="!collapsed" class="whitespace-nowrap text-[14px]">{{ item.label }}</span>
        </router-link>
      </li>
    </ul>

    <div class="p-2 border-t border-gray-700 shrink-0">
      <button @click="$emit('toggle')" class="w-full flex justify-center p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors">
        <component :is="collapsed ? ChevronRight : ChevronLeft" class="size-5" />
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
// Import Lucide Icons
import { 
  LayoutDashboard, Barcode, HardDrive, Save, Keyboard, 
  Droplets, Users, Truck, Wrench, History, 
  ArrowLeftRight, Undo2, Ticket, FileText, Settings, LogIn, 
  Headset, MessageSquare, Home, Laptop, Network, 
  PlusCircle, User, ChevronLeft, ChevronRight 
} from 'lucide-vue-next';

defineProps({
  collapsed: Boolean
});

defineEmits(['toggle']);

const userRole = ref('staff'); 
const userName = ref('User');

onMounted(() => {
  const userData = localStorage.getItem('user_data');
  if (userData) {
    const user = JSON.parse(userData);
    userName.value = user.name || 'User';
    userRole.value = user.role ? user.role.toLowerCase() : 'staff'; 
  }
});

const menuItems = [
  // ADMIN SECTION
  { label: 'Dashboard', icon: LayoutDashboard, path: '/dashboard/admin', roles: ['admin'] },
  { label: 'Assets', icon: Barcode, path: '/dashboard/admin/assets', roles: ['admin'] },
  { label: 'Components', icon: HardDrive, path: '/dashboard/admin/components', roles: ['admin'] },
  { label: 'Licenses', icon: Save, path: '/dashboard/admin/licenses', roles: ['admin'] },
  { label: 'Accessories', icon: Keyboard, path: '/dashboard/admin/accessories', roles: ['admin'] },
  { label: 'Consumables', icon: Droplets, path: '/dashboard/admin/consumables', roles: ['admin'] },
  { label: 'People', icon: Users, path: '/dashboard/admin/people', roles: ['admin'] },
  { label: 'Suppliers', icon: Truck, path: '/dashboard/admin/suppliers', roles: ['admin'] },
  { label: 'Maintenances', icon: Wrench, path: '/dashboard/admin/maintenances', roles: ['admin'] }, 
  { label: 'Activity Logs', icon: History, path: '/dashboard/admin/logs', roles: ['admin'] },       
  { label: 'Transfer Assets', icon: ArrowLeftRight, path: '/dashboard/admin/transfers/assets', roles: ['admin'] },
  { label: 'Return Assets', icon: Undo2, path: '/dashboard/admin/transfers/returns', roles: ['admin'] },
  { label: 'Tickets', icon: Ticket, path: '/dashboard/admin/tickets', roles: ['admin'] },
  { label: 'Reports', icon: FileText, path: '/dashboard/admin/reports', roles: ['admin'] },       
  { label: 'Settings', icon: Settings, path: '/dashboard/admin/settings', roles: ['admin'] },
  //{ label: 'IT Support', icon: Headset, path: '/dashboard/admin/support', roles: ['admin'] },
  { label: 'Feedback', icon: MessageSquare, path: '/dashboard/admin/feedback', roles: ['admin'] },
  

  // USER/STAFF/HOD SECTION
  { label: 'My Dashboard', icon: Home, path: '/dashboard/user', roles: ['staff', 'hod'] },
  { label: 'My Equipment', icon: Laptop, path: '/dashboard/user/my-assets', roles: ['staff', 'hod'] }, 
  { label: 'My Licenses', icon: Save, path: '/dashboard/user/my-licenses', roles: ['staff', 'hod'] },
  { label: 'My Components', icon: HardDrive, path: '/dashboard/user/my-components', roles: ['staff', 'hod'] },
  { label: 'My Consumables', icon: Droplets, path: '/dashboard/user/my-consumables', roles: ['staff', 'hod'] },
  { label: 'My Accessories', icon: Keyboard, path: '/dashboard/user/my-accessories', roles: ['staff', 'hod'] },
  { label: 'Department Assets', icon: Network, path: '/dashboard/user/department-assets', roles: ['hod'] },
  { label: 'Report an Issue', icon: PlusCircle, path: '/dashboard/user/report-issue', roles: ['staff', 'hod'] },
  { label: 'Support Tickets', icon: Ticket, path: '/dashboard/user/my-tickets', roles: ['staff', 'hod'] },
  { label: 'Request Transfer', icon: ArrowLeftRight, path: '/dashboard/user/request-transfer', roles: ['staff', 'hod'] }, 
  { label: 'Request Return', icon: Undo2, path: '/dashboard/user/request-return', roles: ['staff', 'hod'] },
  { label: 'Inbound Verification', icon: LogIn, path: '/dashboard/user/inbound-verifications', roles: ['staff', 'hod'] },
  { label: 'My Profile', icon: User, path: '/dashboard/user/profile', roles: ['staff', 'hod'] },
  { label: 'Settings', icon: Settings, path: '/dashboard/user/settings', roles: ['staff', 'hod'] },
  { label: 'IT Support', icon: Headset, path: '/dashboard/user/support', roles: ['staff', 'hod'] },
  { label: 'Feedback', icon: MessageSquare, path: '/dashboard/user/feedback', roles: ['staff', 'hod'] },
  
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});
</script>