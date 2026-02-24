<template>
  <div :class="['relative rounded-sm shadow-md text-white min-h-[120px] overflow-hidden transition-transform hover:scale-[1.02]', bgColor]">
    
    <div class="p-4 relative z-10">
      <h3 class="text-4xl font-bold leading-tight mb-1">{{ value }}</h3>
      <p class="text-lg font-medium opacity-90">{{ title }}</p>
    </div>

    <div class="absolute top-2 right-2 text-7xl opacity-20 pointer-events-none z-0">
      <i :class="['fa', icon]"></i>
    </div>

    <a 
      href="#" 
      class="block bg-black/10 hover:bg-black/20 text-center py-1 text-xs uppercase tracking-wider no-underline text-white/80 hover:text-white transition-colors"
    >
      View All <i class="fa fa-arrow-circle-right ml-1"></i>
    </a>
  </div>
</template>

<script setup>
// Props definition with defaults for a robust component architecture
defineProps({
  title: {
    type: String,
    required: true,
    default: 'Total Assets'
  },
  value: {
    type: [String, Number],
    required: true,
    default: 0
  },
  icon: {
    type: String,
    default: 'fa-barcode'
  },
  bgColor: {
    type: String,
    default: 'bg-[#00c0ef]' // Default to the Aqua blue seen in Snipe-IT
  }
});
</script>