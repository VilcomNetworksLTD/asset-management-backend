<template>
  <nav class="sticky top-0 left-0 right-0 h-20 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-8 z-[1000] shadow-2xl">
    <!-- LEFT: BRANDING & NAVIGATION KEYS -->
    <div class="flex items-center gap-10">
      <div class="flex items-center gap-3 group cursor-pointer" @click="goHome">
        <div class="size-10 bg-vilcom-orange rounded-xl flex items-center justify-center text-white font-black italic shadow-lg group-hover:scale-110 transition-transform duration-300">V</div>
        <span class="text-white font-black tracking-tighter text-2xl group-hover:text-vilcom-orange transition-colors">VILCOM</span>
      </div>

      <!-- NAVIGATION KEYS & BREADCRUMBS -->
      <div class="flex items-center gap-4 bg-slate-800/50 p-1 px-3 rounded-xl border border-slate-700/50">
        <div class="flex items-center gap-1">
          <button 
            @click="goBack" 
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-700/80 rounded-lg transition-all active:scale-90"
            title="Go Back"
          >
            <ChevronLeft class="size-4" />
          </button>
          <button 
            @click="goForward" 
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-700/80 rounded-lg transition-all active:scale-90"
            title="Go Forward"
          >
            <ChevronRight class="size-4" />
          </button>
        </div>
        
        <div class="w-px h-6 bg-slate-700/50 hidden md:block"></div>
        
        <!-- BREADCRUMBS -->
        <div class="hidden md:flex items-center gap-2 overflow-hidden max-w-[300px]">
          <template v-for="(crumb, index) in breadcrumbs" :key="index">
            <span v-if="index > 0" class="text-slate-600 font-bold text-[10px]">/</span>
            <router-link 
              :to="crumb.path"
              class="text-[10px] font-black uppercase tracking-widest truncate transition-colors"
              :class="index === breadcrumbs.length - 1 ? 'text-vilcom-orange' : 'text-slate-500 hover:text-slate-300'"
            >
              {{ crumb.label }}
            </router-link>
          </template>
        </div>
      </div>
    </div>

    <!-- CENTER: HUB NAVIGATION (TASK BAR) -->
    <div class="flex items-center gap-2 bg-slate-950/30 p-1.5 rounded-2xl border border-slate-800/50">
      <router-link 
        v-for="item in filteredMenuItems" 
        :key="item.label"
        :to="item.path"
        class="flex items-center gap-3 px-5 py-2.5 rounded-xl text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] transition-all duration-300 hover:text-white hover:bg-slate-800 hover:shadow-lg"
        active-class="!text-white bg-vilcom-blue shadow-lg shadow-blue-900/40 relative"
      >
        <component :is="item.icon" class="size-4" />
        <span class="hidden xl:block">{{ item.label }}</span>
        
        <!-- Active Indicator -->
        <div v-if="$route.path.startsWith(item.path)" class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 size-1 bg-vilcom-orange rounded-full"></div>
      </router-link>
    </div>

    <!-- RIGHT: SEARCH & PROFILE -->
    <div class="flex items-center gap-6">
      <div class="relative hidden lg:block group">
        <input 
          type="text" 
          placeholder="Sector Scan..." 
          class="bg-slate-800 border-none rounded-xl py-2.5 pl-10 pr-6 text-xs text-white ring-1 ring-slate-700 focus:ring-2 focus:ring-vilcom-blue transition-all w-48 shadow-inner"
        />
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-500 group-focus-within:text-vilcom-blue transition-colors" />
      </div>

      <div class="flex items-center gap-4 bg-slate-800/30 p-1 px-4 rounded-2xl border border-slate-800/50 hover:border-slate-700 transition-colors">
        <div class="text-right">
          <p class="text-[10px] font-black text-white leading-tight uppercase tracking-widest">{{ userName }}</p>
          <p class="text-[8px] text-gray-500 font-bold uppercase tracking-tighter">{{ userRole }} Access</p>
        </div>
        <img src="https://ui-avatars.com/api/?name=Admin&background=1e40af&color=fff" class="size-9 rounded-xl border border-slate-700" alt="Avatar">
        
        <button @click="handleLogout" class="p-2 text-slate-500 hover:text-red-500 hover:bg-slate-800 rounded-lg transition-all" title="Secure Eject">
          <Power class="size-4" />
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  LayoutDashboard, Box, ArrowLeftRight, Users, 
  Settings, ChevronLeft, ChevronRight, Home, 
  Package, ListTodo, Search, Power, ShieldCheck
} from 'lucide-vue-next';
import axios from 'axios';

const router = useRouter();
const route = useRoute();

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
  { label: 'Inventory', icon: Box, path: '/dashboard/admin/inventory', roles: ['admin'] },
  { label: 'Movements', icon: ArrowLeftRight, path: '/dashboard/admin/movements', roles: ['admin'] },
  { label: 'Directory', icon: Users, path: '/dashboard/admin/directory', roles: ['admin'] },
  { label: 'Operations', icon: ListTodo, path: '/dashboard/admin/operations', roles: ['admin'] },
  { label: 'System Settings', icon: Settings, path: '/dashboard/admin/settings', roles: ['admin'] },
  
  // STAFF/HOD SECTION
  { label: 'My Hub', icon: Home, path: '/dashboard/user', roles: ['staff', 'hod', 'management'] },
  { label: 'My Workspace', icon: Package, path: '/dashboard/user/workspace', roles: ['staff', 'hod', 'management'] },
  { label: 'Dept. Assets', icon: Box, path: '/dashboard/user/department-assets', roles: ['hod'] },
  { label: 'Task List', icon: ListTodo, path: '/dashboard/user/manage-assets', roles: ['hod'] },
  { label: 'Definitions', icon: Settings, path: '/dashboard/user/manage-definitions', roles: ['hod'] },
  { label: 'Profile Settings', icon: Settings, path: '/dashboard/user/settings', roles: ['staff', 'hod', 'management'] },
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});

const breadcrumbs = computed(() => {
  const pathArray = route.path.split('/').filter(p => p && p !== 'dashboard');
  let currentPath = '/dashboard';
  
  return pathArray.map((segment, index) => {
    currentPath += `/${segment}`;
    
    // Format segment label
    let label = segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' ');
    
    // Special mapping for common segments
    if (segment === 'admin') label = 'System';
    if (segment === 'user') label = 'Portal';
    if (segment === 'inventory') label = 'Hardware';
    if (segment === 'workspace') label = 'My Assets';
    
    return {
      label,
      path: currentPath
    };
  });
});

const goBack = () => router.go(-1);
const goForward = () => router.go(1);
const goHome = () => router.push(userRole.value === 'admin' ? '/dashboard/admin' : '/dashboard/user');

const handleLogout = async () => {
  if (!confirm('Execute secure logout sequence?')) return;
  try {
    const token = localStorage.getItem('user_token')
    if (token) {
      await axios.post('/api/logout', {}, {
        headers: { Authorization: `Bearer ${token}` }
      })
    }
  } catch (err) {
    console.error('Logout failed', err)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_token')
    localStorage.removeItem('user_data')
    router.push('/')
  }
};
</script>

<style scoped>
.router-link-active {
  box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.4), 0 4px 6px -2px rgba(30, 64, 175, 0.1);
}
</style>
