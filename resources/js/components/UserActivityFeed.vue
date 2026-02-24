<template>
  <div class="bg-white rounded shadow-lg border-t-4 border-[#f97316]">
    <div class="p-4 border-b bg-gray-50">
      <h3 class="font-bold text-[#1e3a8a] text-sm uppercase">Recent Activity</h3>
    </div>
    
    <div class="p-4 space-y-4">
      <div 
        v-for="log in activities" 
        :key="log.id" 
        class="flex gap-3 items-start pb-4 border-b border-gray-50 last:border-0"
      >
        <div 
          :class="[log.color || 'bg-gray-300']" 
          class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
        ></div>
        
        <div class="flex-1">
          <p class="text-xs text-gray-700 leading-tight">
            <span class="font-bold text-[#1e3a8a]">{{ log.type }}:</span> 
            {{ log.message }}
          </p>
          <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider font-semibold">
            <i class="fa fa-clock-o mr-1"></i> {{ log.time }}
          </p>
        </div>
      </div>

      <div v-if="activities.length === 0" class="py-10 text-center">
        <i class="fa fa-history text-gray-200 text-3xl mb-2"></i>
        <p class="text-xs text-gray-400 italic">No recent activity to show.</p>
      </div>
    </div>

    <div class="p-3 bg-gray-50 text-center border-t">
      <button class="text-[10px] font-black uppercase text-[#f97316] hover:underline">
        View Full History
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Expects an array of objects:
 * { id: Number, type: String, message: String, time: String, color: String }
 */
defineProps({
  activities: {
    type: Array,
    default: () => []
  }
});
</script>

<style scoped>
/* Optional: Adding a subtle hover effect to activity rows */
.flex:hover {
  background-color: rgba(249, 115, 22, 0.02);
  transition: background-color 0.2s ease;
}
</style>