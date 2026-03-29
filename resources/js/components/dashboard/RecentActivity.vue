<template>
  <div class="bg-white shadow-sm border border-gray-100 rounded-[2rem] overflow-hidden h-full flex flex-col group transition-all duration-300 hover:shadow-lg">
    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center">
      <div>
        <h3 class="text-xl font-black text-slate-800 tracking-tight">Recent Activity</h3>
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Audit log & actions</p>
      </div>
      <div class="flex gap-2">
         <button class="p-2 text-gray-400 hover:bg-gray-50 rounded-lg transition-colors"><i class="fa fa-sync-alt text-sm"></i></button>
      </div>
    </div>

    <div class="flex-1 overflow-x-auto custom-scrollbar">
      <table class="w-full text-sm text-left">
        <thead>
          <tr class="bg-slate-50/50">
            <th class="px-8 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Event</th>
            <th class="px-6 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Details</th>
            <th class="px-6 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest">Actor</th>
            <th class="px-8 py-4 font-black text-[10px] text-gray-400 uppercase tracking-widest text-right">Timestamp</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr 
            v-for="(item, index) in mappedActivities" 
            :key="item.id ?? index"
            class="hover:bg-blue-50/30 transition-colors group/row"
          >
            <td class="px-8 py-4">
              <div class="flex items-center gap-4">
                <div :class="['size-10 rounded-xl flex items-center justify-center shadow-sm transition-transform group-hover/row:scale-110', item.iconBg]">
                   <i :class="[item.icon, 'text-white']"></i>
                </div>
                <div class="font-bold text-slate-700 truncate max-w-[120px]">{{ item.action }}</div>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-800">{{ item.item }}</span>
                <span v-if="item.target" class="text-[10px] text-gray-400 flex items-center gap-1 mt-0.5">
                  <User class="size-2.5" /> {{ item.target }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4">
               <div class="flex items-center gap-2">
                 <img :src="`https://ui-avatars.com/api/?name=${item.createdBy}&background=f1f5f9&color=64748b`" class="size-6 rounded-lg opacity-80" alt="">
                 <span class="text-xs font-bold text-slate-600">{{ item.createdBy }}</span>
               </div>
            </td>
            <td class="px-8 py-4 text-right">
              <span class="text-[11px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded text-nowrap">{{ item.dateString }}</span>
            </td>
          </tr>
          <tr v-if="mappedActivities.length === 0">
            <td colspan="4" class="p-12 text-center">
               <div class="flex flex-col items-center opacity-30">
                 <History class="size-12 mb-4" />
                 <p class="font-bold">No recent activities recorded</p>
               </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="px-8 py-4 bg-slate-50/50 border-t border-gray-50 text-center">
      <router-link to="/dashboard/admin/logs" class="text-[10px] text-vilcom-blue uppercase font-black tracking-widest hover:underline hover:scale-105 transition-transform inline-flex items-center gap-2">
        Browse Full Audit Trail <ArrowRight class="size-3" />
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { User, History, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

const activityMeta = (action = '') => {
  const lower = String(action).toLowerCase()
  if (['update', 'updated', 'edit'].includes(lower)) return { icon: 'fas fa-pen-nib', bg: 'bg-vilcom-blue' }
  if (['requested', 'pending'].includes(lower)) return { icon: 'fas fa-clock', bg: 'bg-vilcom-orange' }
  if (['checkout', 'deployed', 'assigned'].includes(lower)) return { icon: 'fas fa-rocket', bg: 'bg-teal-500' }
  if (['returned', 'return'].includes(lower)) return { icon: 'fas fa-undo-alt', bg: 'bg-indigo-500' }
  if (['delete', 'deleted', 'removed'].includes(lower)) return { icon: 'fas fa-trash-alt', bg: 'bg-red-500' }
  return { icon: 'fas fa-bolt', bg: 'bg-slate-400' }
}

const mappedActivities = computed(() => {
  const acts = Array.isArray(props.activities) 
    ? props.activities 
    : (props.activities && Array.isArray(props.activities.data) ? props.activities.data : []);
    
  return acts.slice(0, 8).map((item) => {
    let extractedTarget = item.target;
    if (!extractedTarget && item.details) {
      const match = item.details.match(/user:\s*([^(]+)/);
      if (match) extractedTarget = match[1].trim();
    }
    
    const meta = activityMeta(item.action);

    return {
      id: item.user?.name || item.user_name || 'System',
      icon: meta.icon,
      iconBg: meta.bg,
      dateString: item.created_at ? new Date(item.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-',
      createdBy: item.user?.name || item.user_name || 'System',
      action: item.action || '-',
      item: item.target_name || item.item || '-',
      target: extractedTarget || null,
    };
  });
})
</script>