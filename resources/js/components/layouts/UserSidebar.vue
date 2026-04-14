<template>
  <aside 
    class="h-screen flex flex-col fixed left-0 top-16 shadow-2xl transition-all duration-300" 
    :class="compactSidebar ? 'w-20' : 'w-64'"
    style="background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); z-index: 9999;"
  >
    <!-- NAVIGATION -->
    <ul class="flex-1 flex flex-col py-4 px-3 list-none m-0 gap-2 overflow-y-auto">
      <li 
        v-for="item in filteredMenuItems" 
        :key="item.label"
        class="flex flex-col"
      >
        <router-link 
          :to="item.path" 
          class="group flex items-center justify-center lg:justify-start px-4 py-3.5 no-underline rounded-xl transition-all duration-200"
          :class="compactSidebar ? 'lg:px-3' : 'px-4'"
        >
          <div 
            class="flex items-center justify-center shrink-0 rounded-xl transition-all duration-200"
            :class="compactSidebar ? 'lg:w-10 lg:h-10 mb-1' : 'w-10 h-10 mr-3'"
            style="background: linear-gradient(135deg, rgba(30, 64, 175, 0.2) 0%, rgba(30, 64, 175, 0.1) 100%);"
          >
            <component :is="item.icon" class="size-5 text-slate-400 group-hover:text-vilcom-orange transition-colors" />
          </div>
          <span v-if="!compactSidebar" class="whitespace-nowrap text-sm font-bold text-slate-400 group-hover:text-vilcom-orange transition-colors">{{ item.label }}</span>
        </router-link>
      </li>
    </ul>

    <!-- FOOTER -->
    <div v-if="!compactSidebar" class="p-4 border-t border-slate-700/50 shrink-0">
      <div class="p-4 bg-slate-800/50 rounded-xl">
        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Vilcom Asset Manager</p>
        <p class="text-xs text-slate-400 mt-1">v2.0.0</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { 
  LayoutDashboard, Home, Box, ArrowLeftRight, Settings, Package, ListTodo, User, ShoppingCart
} from 'lucide-vue-next';

const userRole = ref('staff'); 
const compactSidebar = ref(false);

onMounted(() => {
  const userData = localStorage.getItem('user_data');
  if (userData) {
    const user = JSON.parse(userData);
    userRole.value = user.role ? user.role.toLowerCase() : 'staff'; 
  }

  compactSidebar.value = localStorage.getItem('compactSidebar') === 'true';
  
  window.addEventListener('toggle-compact-sidebar', handleCompactToggle);
});

onUnmounted(() => {
  window.removeEventListener('toggle-compact-sidebar', handleCompactToggle);
});

const handleCompactToggle = (event) => {
  compactSidebar.value = event.detail;
};

const menuItems = [
  { label: 'My Dashboard', icon: Home, path: '/dashboard/user', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'My Workspace', icon: Package, path: '/dashboard/user/workspace', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'Dept. Assets', icon: Box, path: '/dashboard/user/department-assets', roles: ['hod', 'manager'] },
  { label: 'Manage Assets', icon: ListTodo, path: '/dashboard/user/manage-assets', roles: ['hod', 'manager'] },
  { label: 'My Profile', icon: User, path: '/dashboard/user/profile', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'Purchase Escalations', icon: ShoppingCart, path: '/dashboard/user/purchase-escalations', roles: ['management'] },
  { label: 'Settings', icon: Settings, path: '/dashboard/user/settings', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});
</script>
