<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col font-inter">
    <!-- TOP NAVIGATION HUB (New Architecture) -->
    <TopNav />

    <!-- MAIN APP SURFACE -->
    <main class="flex-1 flex flex-col min-w-0">
      <div class="p-8 max-w-[1700px] mx-auto w-full relative">
        <!-- Optional Global Breadcrumb/Status Bar could go here -->
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

    <!-- FOOTER (Optional/Administrative) -->
    <footer class="py-6 px-12 border-t border-gray-100 flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] bg-white">
       <div>© 2026 Vilcom Networks LTD | Asset Management System v2.0</div>
       <div class="flex gap-6">
         <span class="hover:text-vilcom-blue cursor-pointer">Security Protocol V3</span>
         <span class="hover:text-vilcom-blue cursor-pointer">Support API</span>
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
    if (user.role?.toLowerCase() !== 'admin') {
      router.push({ name: 'user-dashboard' })
    }
  } catch (err) {
    console.error('Session corruption detected', err)
    localStorage.clear()
    router.push({ name: 'login' })
  }
})
</script>

<style>
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
