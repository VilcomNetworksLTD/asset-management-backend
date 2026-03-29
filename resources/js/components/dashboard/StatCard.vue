<template>
  <router-link 
    :to="link"
    :class="[
      'relative group p-6 rounded-3xl border border-gray-100 shadow-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-1 overflow-hidden',
      bgColor
    ]"
  >
    <!-- Background Decor -->
    <div class="absolute -right-6 -top-6 size-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>

    <div class="relative z-10 flex flex-col h-full justify-between">
      <div class="flex items-start justify-between mb-4">
        <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
          <i :class="['fa text-2xl text-white', icon]"></i>
        </div>
        <div class="text-white/40 group-hover:text-white transition-colors">
          <i class="fa fa-external-link-alt text-xs"></i>
        </div>
      </div>

      <div>
        <h3 class="text-3xl font-black text-white leading-none mb-1 tracking-tight">{{ value }}</h3>
        <p class="text-sm font-bold text-white/80 uppercase tracking-widest">{{ title }}</p>
      </div>
    </div>
  </router-link>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: 'fa-barcode'
  },
  bgColor: {
    type: String,
    default: 'bg-vilcom-blue'
  },
  link: {
    type: String,
    default: '#'
  }
});
</script>