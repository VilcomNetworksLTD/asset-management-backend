<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col font-inter">
    <!-- TOP NAVIGATION -->
    <TopNav />

    <!-- MAIN CONTENT AREA -->
    <div class="flex flex-1 pt-16 relative">
      <!-- SIDEBAR NAVIGATION -->
      <UserSidebar :is-open="sidebarOpen" :is-compact="compactSidebar" @close="sidebarOpen = false" />

      <!-- MOBILE OVERLAY -->
      <div 
        v-if="sidebarOpen" 
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-[9998] lg:hidden backdrop-blur-sm transition-opacity"
      ></div>

      <!-- MAIN CONTENT -->
      <div 
        class="flex-1 transition-all duration-300 min-w-0"
        :class="[
          compactSidebar ? 'lg:ml-20' : 'lg:ml-64',
          'ml-0'
        ]"
      >
        <main class="p-4 md:p-8 max-w-[1700px] mx-auto w-full">
          <router-view v-slot="{ Component }">
            <transition name="fade-slide" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import eventBus from '../../eventBus'
import TopNav from './TopNav.vue'
import UserSidebar from './UserSidebar.vue'

const router = useRouter()
const sidebarOpen = ref(false)
const compactSidebar = ref(localStorage.getItem('compactSidebar') === 'true')

onMounted(() => {
  const userData = localStorage.getItem('user_data')
  if (!userData) {
    router.push({ name: 'login' })
  }

  eventBus.on('toggle-sidebar', () => {
    sidebarOpen.value = !sidebarOpen.value
  })

  eventBus.on('toggle-compact-sidebar', (val) => {
    compactSidebar.value = val
  })
})

onBeforeUnmount(() => {
  eventBus.off('toggle-sidebar')
  eventBus.off('toggle-compact-sidebar')
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
