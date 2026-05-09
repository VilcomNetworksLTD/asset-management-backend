<template>
  <aside 
    class="bg-slate-900 h-screen overflow-y-auto flex flex-col fixed left-0 top-16 z-[9999] shadow-2xl transition-all duration-300"
    :class="[
      isCompact ? 'w-20' : 'w-64',
      isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <!-- USER PROFILE -->
    <div class="px-4 py-6 border-b border-slate-800 shrink-0 overflow-hidden">
      <div class="flex items-center gap-3">
        <img :src="`https://ui-avatars.com/api/?name=${userName}&background=1e40af&color=fff`" class="size-10 rounded-xl border-2 border-slate-700 shrink-0" alt="Avatar">
        <div v-if="!isCompact" class="overflow-hidden transition-opacity duration-300">
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
          @click="$emit('close')"
          class="group flex items-center px-4 py-3 no-underline rounded-xl transition-all duration-200 text-slate-400 hover:text-vilcom-orange hover:bg-slate-800"
          active-class="!text-white bg-vilcom-orange shadow-lg shadow-orange-900/20"
          :title="isCompact ? item.label : ''"
        >
          <div class="flex items-center justify-center shrink-0 w-6" :class="!isCompact ? 'mr-3' : ''">
            <component :is="item.icon" class="size-5" />
          </div>
          <span v-if="!isCompact" class="whitespace-nowrap text-sm font-semibold transition-opacity duration-300">{{ item.label }}</span>
        </router-link>
      </li>
    </ul>

  </aside>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  Home, Package, Box, ListTodo, Settings, UserCircle, ShoppingBag, BookOpen
} from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  isCompact: Boolean
});

defineEmits(['close']);

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
  { label: 'My Dashboard', icon: Home, path: '/dashboard/user', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'My Workspace', icon: Package, path: '/dashboard/user/workspace', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'Dept. Assets', icon: Box, path: '/dashboard/user/department-assets', roles: ['hod', 'manager'] },
  { label: 'Manage Assets', icon: ListTodo, path: '/dashboard/user/manage-assets', roles: ['hod', 'manager'] },
  { label: 'Definitions', icon: BookOpen, path: '/dashboard/user/manage-definitions', roles: ['hod', 'manager'] },
  { label: 'Purchase Approvals', icon: ShoppingBag, path: '/dashboard/user/purchase-escalations', roles: ['management', 'admin'] },
  { label: 'My Profile', icon: UserCircle, path: '/dashboard/user/profile', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
  { label: 'Settings', icon: Settings, path: '/dashboard/user/settings', roles: ['staff', 'employee', 'hod', 'manager', 'management'] },
];

const filteredMenuItems = computed(() => {
  return menuItems.filter(item => item.roles.includes(userRole.value));
});
</script>