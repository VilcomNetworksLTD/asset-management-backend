<template>
  <div class="bg-white border-t-4 border-[#d2d6de] shadow-md rounded-sm mb-5">
    <div class="px-4 py-3 border-b border-[#f4f4f4] flex justify-between items-center">
      <h3 class="text-lg font-normal text-[#444]">Recent Activity</h3>
      <div class="flex gap-2 text-gray-300 text-xs">
        <button class="hover:text-gray-600"><i class="fa fa-minus"></i></button>
        <button class="hover:text-gray-600"><i class="fa fa-times"></i></button>
      </div>
    </div>

    <div class="overflow-x-auto p-0">
      <table class="w-full text-sm text-left border-collapse">
        <thead>
          <tr class="text-[#333] border-b-2 border-[#f4f4f4]">
            <th class="p-3 w-10"></th>
            <th class="p-3 font-semibold">Date</th>
            <th class="p-3 font-semibold">Created By</th>
            <th class="p-3 font-semibold">Action</th>
            <th class="p-3 font-semibold">Item</th>
            <th class="p-3 font-semibold">Target</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f4f4f4]">
          <tr 
            v-for="(item, index) in mappedActivities" 
            :key="item.id ?? index"
            class="hover:bg-[#f9f9f9] transition-colors"
          >
            <td class="p-3 text-center">
              <i :class="[item.icon, 'text-gray-600']"></i>
            </td>
            <td class="p-3 text-gray-700 whitespace-nowrap">{{ item.date }}</td>
            <td class="p-3">
              <a href="#" class="text-[#3c8dbc] hover:text-[#286090] no-underline font-medium">
                {{ item.createdBy }}
              </a>
            </td>
            <td class="p-3 text-gray-600">{{ item.action }}</td>
            <td class="p-3">
              <a href="#" class="text-[#3c8dbc] hover:text-[#286090] no-underline">
                {{ item.item }}
              </a>
            </td>
            <td class="p-3">
              <div v-if="item.target" class="flex items-center gap-1">
                <i class="fa fa-user text-gray-400 text-xs"></i>
                <a href="#" class="text-[#3c8dbc] hover:text-[#286090] no-underline">
                  {{ item.target }}
                </a>
              </div>
            </td>
          </tr>
          <tr v-if="mappedActivities.length === 0">
            <td colspan="6" class="p-6 text-center text-gray-400 italic">No recent activity found.</td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="px-4 py-2 border-t border-[#f4f4f4] text-center">
      <a href="#" class="text-xs text-gray-500 uppercase font-bold hover:text-[#3c8dbc]">View All Activity</a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

const iconForAction = (action = '') => {
  const lower = String(action).toLowerCase()
  if (['update', 'updated', 'edit'].includes(lower)) return 'fas fa-pencil-alt'
  if (['requested', 'pending'].includes(lower)) return 'fas fa-sync'
  if (['checkout', 'deployed', 'assigned'].includes(lower)) return 'fas fa-arrow-right'
  if (['returned', 'return'].includes(lower)) return 'fas fa-undo'
  return 'fas fa-circle'
}

const mappedActivities = computed(() =>
  (props.activities || []).map((item) => ({
    id: item.id,
    icon: iconForAction(item.action),
    date: item.created_at ? new Date(item.created_at).toLocaleString() : (item.date || '-'),
    createdBy: item.user_name || item.createdBy || 'System',
    action: item.action || '-',
    item: item.target_name || item.item || '-',
    target: item.target || null,
  }))
)
</script>