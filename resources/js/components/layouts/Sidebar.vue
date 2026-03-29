<template>
  <aside 
    :class="[
      'bg-slate-900 h-screen overflow-y-auto transition-all duration-300 ease-in-out flex flex-col z-50 shadow-2xl',
      collapsed ? 'w-20' : 'w-64'
    ]"
  >
    <!-- BRANDING -->
    <div class="h-16 flex items-center px-6 bg-slate-950 shrink-0">
      <div v-if="!collapsed" class="flex items-center gap-3">
        <div class="size-8 bg-vilcom-orange rounded-lg flex items-center justify-center text-white font-black italic">V</div>
        <span class="text-white font-black tracking-tighter text-xl">VILCOM</span>
      </div>
      <div v-else class="w-full flex justify-center">
        <div class="size-8 bg-vilcom-orange rounded-lg flex items-center justify-center text-white font-black italic shadow-lg">V</div>
      </div>
    </div>

    <!-- USER PROFILE (Optional, hidden when collapsed) -->
    <div v-if="!collapsed" class="px-6 py-6 border-b border-slate-800 shrink-0">
      <div class="flex items-center gap-3">
        <img src="https://ui-avatars.com/api/?name=Admin&background=1e40af&color=fff" class="size-10 rounded-xl border-2 border-slate-700" alt="Avatar">
        <div class="overflow-hidden">
          <p class="text-white text-sm font-bold truncate">{{ userName }}</p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ userRole }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- NAVIGATION -->
    <ul class="flex-1 flex flex-col py-4 px-3 list-none m-0 gap-1">
      <li 
        v-for="item in filteredMenuItems" 
        :key="item.label"
        class="flex flex-col"
      >
        <router-link 
          :to="item.path" 
          class="group flex items-center px-4 py-3 no-underline rounded-xl transition-all duration-200 text-slate-400 hover:text-white hover:bg-slate-800"
          active-class="!text-white bg-vilcom-blue shadow-lg shadow-blue-900/20"
          :title="collapsed ? item.label : ''"
        >
          <div :class="['flex items-center justify-center shrink-0 transition-transform group-hover:scale-110', collapsed ? 'w-full' : 'w-6 mr-3']">
            <component :is="item.icon" :class="[collapsed ? 'size-6' : 'size-5']" />
          </div>
          <span v-if="!collapsed" class="whitespace-nowrap text-sm font-semibold">{{ item.label }}</span>
        </router-link>
      </li>
    </ul>

    <!-- COLLAPSE TOGGLE -->
    <div class="p-4 border-t border-slate-800 shrink-0">
      <button @click="$emit('toggle')" class="w-full flex items-center justify-center py-2 px-3 text-slate-500 hover:text-white hover:bg-slate-800 rounded-xl transition-all">
        <component :is="collapsed ? ChevronRight : ChevronLeft" class="size-5" />
        <span v-if="!collapsed" class="ml-2 text-xs font-bold uppercase tracking-widest">Collapse Sidebar</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  LayoutDashboard, Box, ArrowLeftRight, Users, 
  Settings, ChevronLeft, ChevronRight, Home, Wrench, Package, ListTodo
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
  { label: 'Inventory Hub', icon: Box, path: '/dashboard/admin/inventory', roles: ['admin'] },
  { label: 'Movements Hub', icon: ArrowLeftRight, path: '/dashboard/admin/movements', roles: ['admin'] },
  { label: 'Directory', icon: Users, path: '/dashboard/admin/directory', roles: ['admin'] },
  { label: 'Operations', icon: ListTodo, path: '/dashboard/admin/operations', roles: ['admin'] },
  { label: 'Settings', icon: Settings, path: '/dashboard/admin/settings', roles: ['admin'] },
  
  // STAFF/HOD SECTION
  { label: 'My Dashboard', icon: Home, path: '/dashboard/user', roles: ['staff', 'hod'] },
  { label: 'My Workspace', icon: Package, path: '/dashboard/user/workspace', roles: ['staff', 'hod'] },
  { label: 'Dept. Assets', icon: Box, path: '/dashboard/user/department-assets', roles: ['hod'] },
  { label: 'Manage Assets', icon: ListTodo, path: '/dashboard/user/manage-assets', roles: ['hod'] },
  { label: 'Settings', icon: Settings, path: '/dashboard/user/settings', roles: ['staff', 'hod'] },
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});
</script>