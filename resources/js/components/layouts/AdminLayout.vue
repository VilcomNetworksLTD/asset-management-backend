<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col font-inter">
    <!-- TOP NAVIGATION -->
    <TopNav />

    <!-- MAIN CONTENT AREA -->
    <div class="flex flex-1 pt-16 relative">
      <!-- SIDEBAR NAVIGATION -->
      <Sidebar />

      <!-- MAIN CONTENT -->
      <div class="flex-1 ml-64">
        <main class="p-8 max-w-[1700px] mx-auto w-full">
          <router-view v-slot="{ Component }">
            <transition name="fade-slide" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>

        <!-- FOOTER -->
        <footer class="py-6 px-12 border-t border-gray-100 flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] bg-white">
           <div>© 2026 Vilcom Networks LTD | Asset Management System v2.0</div>
           <div class="flex gap-6">
             <span class="hover:text-vilcom-blue cursor-pointer">Security Protocol V3</span>
             <span class="hover:text-vilcom-blue cursor-pointer">Support API</span>
           </div>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import TopNav from './TopNav.vue'
import Sidebar from './Sidebar.vue'

const router = useRouter()

onMounted(() => {
  const userData = localStorage.getItem('user_data')
  if (!userData) {
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
