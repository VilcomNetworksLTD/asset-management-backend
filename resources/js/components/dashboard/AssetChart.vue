<template>
  <div class="bg-white p-8 shadow-sm border border-gray-100 rounded-[2rem] h-full flex flex-col transition-all duration-500 hover:shadow-xl group">
    <div class="flex justify-between items-center mb-10">
      <div>
        <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ title }}</h3>
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Global Distribution</p>
      </div>
      <div class="p-2 bg-gray-50 rounded-xl group-hover:bg-vilcom-blue group-hover:text-white transition-colors duration-300">
        <PieChart class="size-5" />
      </div>
    </div>

    <div class="flex flex-col items-center">
      <div 
        class="relative w-[240px] h-[240px] rounded-full shadow-2xl transition-all duration-700 group-hover:rotate-[360deg] p-4 bg-white"
        :style="{ background: innerChartBackground }"
      >
        <div class="w-full h-full rounded-full bg-white shadow-inner flex items-center justify-center font-black text-2xl text-slate-800">
          {{ totalCount }}
        </div>
      </div>

      <div class="w-full mt-12 space-y-4">
        <div v-for="item in legendItems" :key="item.label" class="flex items-center justify-between p-3 rounded-2xl border border-gray-50 hover:bg-gray-50 transition-colors">
          <span class="flex items-center gap-3">
            <span :class="['w-3 h-3 rounded-full shadow-lg', item.color]"></span>
            <span class="text-sm font-bold text-gray-700">{{ item.label }}</span>
          </span>
          <span class="text-sm font-black text-slate-800 bg-white px-3 py-1 rounded-full shadow-sm">{{ item.value }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PieChart } from 'lucide-vue-next'

const props = defineProps({
  title: {
    type: String,
    default: 'Status Distribution'
  },
  statusDistribution: {
    type: Object,
    default: () => ({ ready_to_deploy: 0, deployed: 0, archived: 0 })
  }
});

const available = computed(() => Number(props.statusDistribution?.ready_to_deploy || 0))
const deployed = computed(() => Number(props.statusDistribution?.deployed || 0))
const archived = computed(() => Number(props.statusDistribution?.archived || 0))
const outForRepair = computed(() => Number(props.statusDistribution?.out_for_repair || 0))
const totalCount = computed(() => available.value + deployed.value + archived.value + outForRepair.value)

const legendItems = computed(() => [
  { label: 'Ready to Deploy', value: available.value, color: 'bg-green-500' },
  { label: 'Deployed', value: deployed.value, color: 'bg-vilcom-blue' },
  { label: 'Archived', value: archived.value, color: 'bg-vilcom-orange' },
  { label: 'Out for Repair', value: outForRepair.value, color: 'bg-red-500' },
]);

const innerChartBackground = computed(() => {
  const total = totalCount.value
  if (total <= 0) return 'conic-gradient(#f3f4f6 0% 100%)'

  const availablePct = (available.value / total) * 100
  const deployedPct = (deployed.value / total) * 100
  const repairPct = (outForRepair.value / total) * 100
  const archivedPct = 100 - availablePct - deployedPct - repairPct

  return `conic-gradient(
    #22c55e 0% ${availablePct}%,
    #1e40af ${availablePct}% ${availablePct + deployedPct}%,
    #ef4444 ${availablePct + deployedPct}% ${availablePct + deployedPct + repairPct}%,
    #f97316 ${availablePct + deployedPct + repairPct}% 100%
  )`
})
</script>