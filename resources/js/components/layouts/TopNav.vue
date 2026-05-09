<template>
  <nav class="sticky top-0 left-0 right-0 h-16 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 z-[1000] shadow-2xl">
    <!-- LEFT: BRANDING & NAVIGATION KEYS -->
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
      <!-- MOBILE MENU TOGGLE -->
      <button 
        @click="toggleSidebar"
        class="lg:hidden p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-all"
      >
        <Menu class="size-6" />
      </button>

      <div class="flex items-center gap-2 sm:gap-3 group cursor-pointer" @click="goHome">
        <img :src="logoUrl" alt="Vilcom Logo" class="h-8 sm:h-10 w-auto object-contain group-hover:scale-105 transition-transform duration-300" />
      </div>

      <!-- NAVIGATION KEYS & BREADCRUMBS -->
      <div class="flex items-center gap-1 sm:gap-4 bg-slate-800/50 p-1 px-1.5 sm:px-3 rounded-xl border border-slate-700/50">
        <div class="flex items-center gap-0.5 sm:gap-1">
          <button 
            @click="goBack" 
            class="p-1.5 sm:p-2 text-slate-400 hover:text-white hover:bg-slate-700/80 rounded-lg transition-all active:scale-90"
            title="Go Back"
          >
            <ChevronLeft class="size-3 sm:size-4" />
          </button>
          <button 
            @click="goForward" 
            class="p-1.5 sm:p-2 text-slate-400 hover:text-white hover:bg-slate-700/80 rounded-lg transition-all active:scale-90"
            title="Go Forward"
          >
            <ChevronRight class="size-3 sm:size-4" />
          </button>
        </div>
        
        <div class="w-px h-6 bg-slate-700/50 hidden sm:block"></div>
        
        <!-- BREADCRUMBS (Visible on sm and up) -->
        <div class="hidden sm:flex items-center gap-2 overflow-hidden max-w-[100px] md:max-w-[300px]">
          <template v-for="(crumb, index) in breadcrumbs" :key="index">
            <span v-if="index > 0" class="text-slate-600 font-bold text-[8px] sm:text-[10px]">/</span>
            <router-link 
              :to="crumb.path"
              class="text-[8px] sm:text-[10px] font-black uppercase tracking-widest truncate transition-colors"
              :class="index === breadcrumbs.length - 1 ? 'text-vilcom-orange' : 'text-slate-500 hover:text-slate-300'"
            >
              {{ crumb.label }}
            </router-link>
          </template>
        </div>
      </div>
    </div>

    <!-- CENTER: SYSTEM STATUS (Hidden on mobile) -->
    <div class="hidden lg:flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">System Active</span>
    </div>

    <!-- RIGHT: SEARCH & PROFILE -->
    <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
      <div class="relative hidden xl:block group">
        <input 
          type="text" 
          v-model="searchQuery"
          @keyup.enter="handleSearch"
          placeholder="Search assets..." 
          class="bg-slate-800 border-none rounded-xl py-2.5 pl-10 pr-6 text-xs text-white ring-1 ring-slate-700 focus:ring-2 focus:ring-vilcom-orange transition-all w-40 shadow-inner"
        />
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-500 group-focus-within:text-vilcom-orange transition-colors" />
      </div>

      <div class="flex items-center gap-1 sm:gap-2 bg-slate-800/30 p-1 px-2 sm:px-3 rounded-2xl border border-slate-800/50 hover:border-slate-700 transition-colors flex-shrink-0">
        <div class="text-right hidden md:block">
          <p class="text-[10px] font-black text-white leading-tight uppercase tracking-widest">{{ userName }}</p>
          <p class="text-[8px] text-gray-500 font-bold uppercase tracking-tighter">{{ userRole }} Access</p>
        </div>
        <img :src="`https://ui-avatars.com/api/?name=${userName}&background=1e40af&color=fff`" class="size-8 sm:size-9 rounded-xl border border-slate-700" alt="Avatar">
        
        <button @click="handleLogout" class="p-1.5 sm:p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-all flex-shrink-0" title="Secure Eject">
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
  ChevronLeft, ChevronRight, Search, Power, Menu
} from 'lucide-vue-next';
import axios from 'axios';
import eventBus from '../../eventBus';
import logoUrl from '@/assets/new_logo.webp';

const router = useRouter();
const route = useRoute();

const userRole = ref('staff'); 
const userName = ref('User');
const searchQuery = ref('');

onMounted(() => {
  const userData = localStorage.getItem('user_data');
  if (userData) {
    const user = JSON.parse(userData);
    userName.value = user.name || 'User';
    userRole.value = user.role ? user.role.toLowerCase() : 'staff'; 
  }
});

const breadcrumbs = computed(() => {
  const pathArray = route.path.split('/').filter(p => p && p !== 'dashboard');
  let currentPath = '/dashboard';
  
  return pathArray.map((segment, index) => {
    currentPath += `/${segment}`;
    
    let label = segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' ');
    
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

const toggleSidebar = () => {
  eventBus.emit('toggle-sidebar');
};

const goBack = () => router.go(-1);
const goForward = () => router.go(1);
const goHome = () => router.push(userRole.value === 'admin' ? '/dashboard/admin' : '/dashboard/user');

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    const path = userRole.value === 'admin' ? '/dashboard/admin/assets' : '/dashboard/user/my-assets';
    router.push({ path, query: { search: searchQuery.value } });
  }
};

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
