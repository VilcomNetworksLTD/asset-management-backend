<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  highlight: {
    type: String,
    default: '',
  },
  buttonText: {
    type: String,
    default: '',
  },
});

defineEmits(['button-click']);
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">
        {{ title }} <span v-if="highlight" class="text-vilcom-blue">{{ highlight }}</span>
      </h1>
      <div class="h-1 w-12 bg-vilcom-orange mt-2 rounded-full"></div>
    </div>
    <button 
      v-if="buttonText" 
      @click="$emit('button-click')" 
      class="px-6 py-2.5 bg-vilcom-blue text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-transform flex items-center gap-2"
    >
      <i class="fa fa-plus text-[10px]"></i> {{ buttonText }}
    </button>
  </div>
</template>
