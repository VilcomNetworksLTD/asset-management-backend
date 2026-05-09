<template>
  <TransitionGroup 
    name="toast" 
    tag="div" 
    class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none"
  >
    <div 
      v-for="toast in toasts" 
      :key="toast.id"
      class="pointer-events-auto min-w-[320px] max-w-[400px] bg-white rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-4 transition-all duration-300 transform"
      :class="[toast.type === 'success' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500']"
    >
      <div 
        class="size-10 rounded-xl flex items-center justify-center shrink-0"
        :class="[toast.type === 'success' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600']"
      >
        <CheckCircle v-if="toast.type === 'success'" class="size-5" />
        <AlertCircle v-else class="size-5" />
      </div>
      
      <div class="flex-1 pt-0.5">
        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest leading-none mb-1">
          {{ toast.type === 'success' ? 'Success' : 'Attention' }}
        </h4>
        <p class="text-xs font-bold text-slate-500 leading-relaxed">{{ toast.message }}</p>
      </div>

      <button @click="remove(toast.id)" class="text-gray-300 hover:text-gray-500 transition-colors">
        <X class="size-4" />
      </button>
    </div>
  </TransitionGroup>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { CheckCircle, AlertCircle, X } from 'lucide-vue-next';

const toasts = ref([]);
let counter = 0;

const add = (message, type = 'success', duration = 5000) => {
  const id = ++counter;
  toasts.value.push({ id, message, type });
  
  if (duration > 0) {
    setTimeout(() => remove(id), duration);
  }
};

const remove = (id) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) toasts.value.splice(index, 1);
};

// Expose to window for easy access from non-setup scripts if needed, 
// though we'll mainly use it via a global property or event bus.
window.vnlNotify = { add };

defineExpose({ add });
</script>

<style scoped>
.toast-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
