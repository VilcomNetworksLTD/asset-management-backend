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
          @focus="isFocused = true"
          @blur="handleBlur"
          placeholder="Search & navigate..." 
          class="bg-slate-800 border-none rounded-xl py-2.5 pl-10 pr-6 text-xs text-white ring-1 ring-slate-700 focus:ring-2 focus:ring-vilcom-orange transition-all w-44 focus:w-80 shadow-inner"
        />
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-500 group-focus-within:text-vilcom-orange transition-colors" />

        <!-- Navigation Assist Dropdown -->
        <div 
          v-if="isFocused && filteredNavPoints.length > 0" 
          class="absolute left-0 mt-2 bg-slate-900 border border-slate-800/80 rounded-2xl shadow-2xl z-[1100] max-h-80 overflow-y-auto custom-scrollbar p-2 space-y-1 w-[24rem]"
        >
          <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest px-3 py-1.5 border-b border-slate-850">
            Suggested Navigation
          </div>
          <button
            v-for="point in filteredNavPoints"
            :key="point.title"
            @mousedown="navigateToPoint(point)"
            class="w-full text-left flex items-start gap-3 p-3 hover:bg-slate-800 rounded-xl transition-colors group/item"
          >
            <div class="p-2 rounded-lg bg-slate-800 group-hover/item:bg-slate-700/85 text-slate-400 group-hover/item:text-vilcom-orange shrink-0">
              <component :is="point.type === 'Button' ? MousePointerClick : Compass" class="size-4" />
            </div>
            <div class="space-y-0.5 min-w-0 flex-1">
              <div class="flex items-center gap-2 justify-between">
                <span class="text-xs font-black text-slate-200 group-hover/item:text-white truncate">{{ point.title }}</span>
                <span 
                  class="text-[7px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider font-mono shrink-0"
                  :class="point.type === 'Button' ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'"
                >
                  {{ point.type }}
                </span>
              </div>
              <p class="text-[9px] text-slate-500 group-hover/item:text-slate-400 truncate">{{ point.description }}</p>
            </div>
          </button>
        </div>
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
  ChevronLeft, ChevronRight, Search, Power, Menu, MousePointerClick, Compass
} from 'lucide-vue-next';
import axios from 'axios';
import eventBus from '../../eventBus';
import logoUrl from '@/assets/new_logo.webp';

const router = useRouter();
const route = useRoute();

const userRole = ref('staff'); 
const userName = ref('User');
const searchQuery = ref('');
const isFocused = ref(false);

const handleBlur = () => {
  setTimeout(() => {
    isFocused.value = false;
  }, 200);
};

const navigateToPoint = (point) => {
  searchQuery.value = '';
  isFocused.value = false;
  router.push({ path: point.path, query: point.query || {} });
};

const navigationPoints = [
  // Admin Navigation Points
  { title: 'Admin Dashboard', type: 'Title', description: 'Overview of system statistics & activities', path: '/dashboard/admin', roles: ['admin'] },
  { title: 'Asset Inventory', type: 'Title', description: 'List & manage hardware inventory', path: '/dashboard/admin/assets', roles: ['admin'] },
  { title: 'Import Assets', type: 'Button', description: 'Upload bulk asset Excel/CSV records', path: '/dashboard/admin/assets/import', roles: ['admin'] },
  { title: 'Accessories Inventory', type: 'Title', description: 'Manage office accessories (keyboards, mice, chargers)', path: '/dashboard/admin/accessories', roles: ['admin'] },
  { title: 'Assign Accessory', type: 'Button', description: 'Assign an accessory to a user', path: '/dashboard/admin/accessories', query: { action: 'assign' }, roles: ['admin'] },
  { title: 'Licenses List', type: 'Title', description: 'Manage software subscriptions & product keys', path: '/dashboard/admin/licenses', roles: ['admin'] },
  { title: 'Toner Lifecycle', type: 'Title', description: 'Track consumable printer toner cartridges', path: '/dashboard/admin/toner-lifecycle', roles: ['admin'] },
  { title: 'Toner Inventory', type: 'Title', description: 'Printer toner inventory list', path: '/dashboard/admin/toner-inventory', roles: ['admin'] },
  { title: 'Maintenance & Repairs', type: 'Title', description: 'Ongoing and completed repair requests', path: '/dashboard/admin/maintenances', roles: ['admin'] },
  { title: 'Solved Repairs / Solve Button', type: 'Button', description: 'Mark a repair request as solved / complete', path: '/dashboard/admin/maintenances', query: { highlight: 'solved' }, roles: ['admin'] },
  { title: 'Transfer Requests', type: 'Title', description: 'Manage device transfers between personnel', path: '/dashboard/admin/transfers/assets', roles: ['admin'] },
  { title: 'Return Requests', type: 'Title', description: 'Review assets being returned to stock', path: '/dashboard/admin/transfers/returns', roles: ['admin'] },
  { title: 'SSL Certificates List', type: 'Title', description: 'Monitor domain SSL expiration status', path: '/dashboard/admin/ssl-certificates', roles: ['admin'] },
  { title: 'Suppliers Directory', type: 'Title', description: 'Manage device vendor contacts', path: '/dashboard/admin/suppliers', roles: ['admin'] },
  { title: 'People / Users List', type: 'Title', description: 'Manage organization users and roles', path: '/dashboard/admin/people', roles: ['admin'] },
  { title: 'Activity Logs', type: 'Title', description: 'Audit trail of all system operations', path: '/dashboard/admin/logs', roles: ['admin'] },
  { title: 'System Reports', type: 'Title', description: 'Generate asset summary reports', path: '/dashboard/admin/reports', roles: ['admin'] },
  { title: 'System Settings', type: 'Title', description: 'Configure custom attributes & system preferences', path: '/dashboard/admin/settings', roles: ['admin'] },
  { title: 'Support Tickets Queue', type: 'Title', description: 'Manage tech support ticket issues', path: '/dashboard/admin/tickets', roles: ['admin'] },
  { title: 'Purchase Approvals Panel', type: 'Title', description: 'Approve or escalate purchase requests', path: '/dashboard/admin/purchase-escalations', roles: ['admin'] },

  // HOD / Manager Navigation Points
  { title: 'Department Assets', type: 'Title', description: 'View assets assigned to your department', path: '/dashboard/user/department-assets', roles: ['hod', 'manager'] },
  { title: 'Manage Assets', type: 'Title', description: 'HOD asset management list', path: '/dashboard/user/manage-assets', roles: ['hod', 'manager'] },
  { title: 'Manage Definitions', type: 'Title', description: 'Department configurations & categories', path: '/dashboard/user/manage-definitions', roles: ['hod', 'manager'] },

  // User/Staff Navigation Points
  { title: 'User Dashboard / Home', type: 'Title', description: 'Personal dashboard portal', path: '/dashboard/user', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'My Workspace', type: 'Title', description: 'My assigned assets, licenses & accessories', path: '/dashboard/user/workspace', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'My Hardware Assets', type: 'Title', description: 'View details of devices assigned to you', path: '/dashboard/user/my-assets', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'My Accessories', type: 'Title', description: 'View accessories assigned to you', path: '/dashboard/user/my-accessories', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'My Licenses', type: 'Title', description: 'View software licenses assigned to you', path: '/dashboard/user/my-licenses', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'Create / Report Issue Ticket', type: 'Button', description: 'Report an issue or open a support ticket', path: '/dashboard/user/report-issue', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'Request Asset Transfer', type: 'Button', description: 'Initiate a transfer request for a device', path: '/dashboard/user/request-transfer', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'Request Asset Return', type: 'Button', description: 'Initiate a return request for a device', path: '/dashboard/user/request-return', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'New Asset Assignment Request', type: 'Button', description: 'Request new hardware assignment', path: '/dashboard/user/new-asset-request', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'Inbound Verifications', type: 'Title', description: 'Accept or decline transferred items', path: '/dashboard/user/inbound-verifications', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'My Profile Details', type: 'Title', description: 'View your profile and employee metadata', path: '/dashboard/user/profile', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'User Settings', type: 'Title', description: 'Manage notification preferences', path: '/dashboard/user/settings', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { title: 'Purchase Requests Escalations', type: 'Title', description: 'Manage escalated purchase approvals', path: '/dashboard/user/purchase-escalations', roles: ['management'] }
];

const filteredNavPoints = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return [];
  return navigationPoints.filter(item => {
    const roleMatches = item.roles.includes(userRole.value);
    const searchMatches = item.title.toLowerCase().includes(query) || 
                          item.description.toLowerCase().includes(query) || 
                          item.type.toLowerCase().includes(query);
    return roleMatches && searchMatches;
  });
});

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
