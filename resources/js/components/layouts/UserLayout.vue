<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col font-inter">
    <!-- TOP NAVIGATION HUB (New Architecture) -->
    <TopNav />

    <!-- MAIN APP SURFACE -->
    <main class="flex-1 flex flex-col min-w-0">
      <div class="p-8 max-w-[1700px] mx-auto w-full relative">
        <router-view v-slot="{ Component }">
          <transition 
            name="fade-slide" 
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>

    <!-- FOOTER -->
    <footer class="py-6 px-12 border-t border-gray-100 flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] bg-white">
       <div>© 2026 Vilcom Networks LTD | Personal Workspace v2.0</div>
       <div class="flex gap-6">
         <span class="hover:text-vilcom-orange cursor-pointer">Staff Portal</span>
         <span class="hover:text-vilcom-orange cursor-pointer">Helpdesk</span>
       </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import TopNav from './TopNav.vue'

const router = useRouter()

onMounted(() => {
  const storedUser = localStorage.getItem('user_data')
  if (!storedUser) {
    router.push({ name: 'login' })
    return
  }

  try {
    const user = JSON.parse(storedUser)
    // Basic role check if needed, though router usually handles this
    if (user.role?.toLowerCase() === 'admin') {
       // Admins can stay in UserLayout if they are viewing user pages, 
       // but usually we redirect them to AdminDashboard if they hit the root
    }
  } catch (err) {
    console.error('Session corruption detected', err)
    localStorage.clear()
    router.push({ name: 'login' })
  }
})
</script>

<style>
/* Transitions are global if defined in layout, but scoped here is fine */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease-out;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Global Scrollbar Refinement */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
