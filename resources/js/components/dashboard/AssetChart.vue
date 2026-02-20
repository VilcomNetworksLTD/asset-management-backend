<template>
  <div class="bg-white border-t-4 border-[#3c8dbc] shadow-md rounded-sm mb-5">
    <div class="px-4 py-3 border-b border-[#f4f4f4] flex justify-between items-center">
      <h3 class="text-lg font-normal text-[#444]">{{ title }}</h3>
      <div class="flex gap-2 text-gray-300 text-xs">
        <button class="hover:text-gray-600"><i class="fa fa-minus"></i></button>
        <button class="hover:text-gray-600"><i class="fa fa-times"></i></button>
      </div>
    </div>

    <div class="p-4 flex flex-col items-center">
      <div 
        class="w-[220px] h-[220px] rounded-full my-5 transition-transform hover:scale-105 duration-500"
        :style="{ background: chartBackground }"
      ></div>

      <div class="w-full mt-4 space-y-2">
        <div class="flex items-center justify-between text-xs border-b border-gray-100 pb-1">
          <span class="flex items-center gap-2">
            <span class="w-3 h-3 bg-[#00a65a] rounded-sm"></span> Ready to Deploy
          </span>
          <span class="font-bold text-gray-600">{{ available }}</span>
        </div>
        <div class="flex items-center justify-between text-xs border-b border-gray-100 pb-1">
          <span class="flex items-center gap-2">
            <span class="w-3 h-3 bg-[#f39c12] rounded-sm"></span> Pending
          </span>
          <span class="font-bold text-gray-600">{{ pending }}</span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="flex items-center gap-2">
            <span class="w-3 h-3 bg-[#dd4b39] rounded-sm"></span> Archived
          </span>
          <span class="font-bold text-gray-600">{{ archived }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: 'Assets by Status' //
  },
  statusDistribution: {
    type: Object,
    default: () => ({ available: 0, pending: 0, archived: 0 })
  }
});

const available = computed(() => Number(props.statusDistribution?.available || 0))
const pending = computed(() => Number(props.statusDistribution?.pending || 0))
const archived = computed(() => Number(props.statusDistribution?.archived || 0))

const chartBackground = computed(() => {
  const total = available.value + pending.value + archived.value
  if (total <= 0) {
    return 'conic-gradient(#e5e7eb 0% 100%)'
  }

  const availablePct = (available.value / total) * 100
  const pendingPct = (pending.value / total) * 100
  const archivedPct = 100 - availablePct - pendingPct

  return `conic-gradient(
    #00a65a 0% ${availablePct}%,
    #f39c12 ${availablePct}% ${availablePct + pendingPct}%,
    #dd4b39 ${availablePct + pendingPct}% ${availablePct + pendingPct + archivedPct}%
  )`
})
</script>