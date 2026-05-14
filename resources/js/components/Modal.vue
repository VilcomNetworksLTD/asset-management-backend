<script setup>
import { X } from 'lucide-vue-next';

defineProps({
  show: Boolean,
  title: String,
});

defineEmits(['close']);
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="$emit('close')"></div>
      
      <!-- Modal container -->
      <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden z-10 flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300">
        <!-- Header -->
        <div class="px-10 py-8 border-b border-gray-50 flex items-center justify-between bg-slate-50/50">
          <div>
             <h3 class="text-xl font-black text-slate-800 tracking-tight">{{ title }}</h3>
             <div class="h-1 w-8 bg-vilcom-orange mt-2 rounded-full"></div>
          </div>
          <button @click="$emit('close')" class="p-2 text-gray-400 hover:bg-white hover:text-red-500 rounded-xl transition-all">
            <X class="size-6" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-10 overflow-y-auto custom-scrollbar flex-1">
          <slot />
        </div>
      </div>
    </div>
  </Transition>
</template>