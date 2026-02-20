<template>
  <header class="fixed top-0 left-0 right-0 h-[50px] bg-[#3c8dbc] flex items-center justify-between z-[900] text-white">
    <div class="w-[360px] h-full bg-[#367fa9] flex items-center px-3 gap-2 flex-shrink-0">
      <img :src="logoUrl" alt="Vilcom Logo" class="h-8 w-8 object-contain" />
      <div class="leading-tight">
        <div class="text-sm font-extrabold tracking-tight">AMS</div>
        <div class="text-[11px] text-blue-100">Vilcom Asset Management System</div>
      </div>
    </div>

    <nav class="flex-1 flex items-center justify-between px-[15px] h-full">
      <div class="flex items-center">
        <button 
          @click="$emit('toggle-sidebar')" 
          class="bg-transparent border-none text-white text-lg cursor-pointer hover:bg-[#367fa9] h-[50px] w-[50px] transition-colors"
        >
          <i class="fa fa-bars"></i>
        </button>
      </div>

      <div class="flex items-center gap-5 h-full">
        <div class="hidden md:flex items-center bg-[#367fa9] rounded-sm px-2 py-1">
          <input 
            type="text" 
            placeholder="Search Assets..." 
            class="bg-transparent border-none text-sm text-white placeholder-blue-100 outline-none w-48"
          />
          <i class="fa fa-search text-blue-200 text-xs"></i>
        </div>

        <div class="flex items-center gap-3 h-full px-3">
          <img 
            :src="user?.avatar || 'https://i.pravatar.cc/150?img=11'" 
            alt="User" 
            class="w-[35px] h-[35px] rounded-full border border-white/20"
          >
          <span class="hidden sm:inline-block text-sm font-medium">
            Welcome back, <span class="font-bold underline italic">{{ user?.name || 'Admin User' }}</span>
          </span>
        </div>

        <button 
          @click="handleLogout" 
          :disabled="loading"
          class="bg-[#dd4b39] hover:bg-[#c23321] text-white text-[10px] font-black uppercase px-4 py-1.5 rounded shadow-sm transition-all flex items-center gap-2 disabled:opacity-50"
        >
          <i class="fa fa-power-off"></i>
          {{ loading ? '...' : 'Logout' }}
        </button>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

defineEmits(['toggle-sidebar']);

const router = useRouter();
const user = ref(null);
const loading = ref(false);
const logoUrl = '/Vlogo.jpeg';

onMounted(() => {
  const savedUser = localStorage.getItem('user_data');
  if (savedUser) {
    try {
      user.value = JSON.parse(savedUser);
    } catch (e) {
      console.error("Failed to parse user data");
    }
  }
});

const handleLogout = async () => {
  if (!confirm('Are you sure you want to log out?')) return;
  
  loading.value = true;
  try {
    // API call to Laravel's logout route
    await axios.post('http://127.0.0.1:8000/api/logout');
  } catch (error) {
    console.error("Logout error:", error);
  } finally {
    // Clean up storage and redirect regardless of API success
    localStorage.removeItem('user_token');
    localStorage.removeItem('user_data');
    delete axios.defaults.headers.common['Authorization'];
    
    loading.value = false;
    router.push('/');
  }
};
</script>