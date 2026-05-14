<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Activity <span class="text-vilcom-blue">Logs</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          System-Wide Audit Trail & Interaction History
        </p>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30">
        <div class="relative group">
          <input 
            v-model="searchQuery" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-80 shadow-sm" 
            placeholder="Filter by user, action, or target..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Timestamp</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Actor</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Operation</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Module / Target</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Transaction Details</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Retrieving Audit Logs...
              </td>
            </tr>
            <tr v-for="log in filteredLogs" :key="log.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <History class="size-5" />
                  </div>
                  <div class="text-[10px] font-bold text-slate-500 font-mono tracking-tighter">
                    {{ formatDate(log.created_at) }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div class="size-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <User class="size-4" />
                  </div>
                  <span class="text-xs font-black text-slate-700">{{ log.user?.name || log.user_name || 'System' }}</span>
                </div>
              </td>
              <td class="px-6 py-5 text-center">
                <span :class="getActionClass(log.action)" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest ring-1 ring-white/50">
                  {{ log.action }}
                </span>
              </td>
              <td class="px-6 py-5">
                <div class="space-y-1">
                  <div class="text-[10px] font-black text-vilcom-blue uppercase tracking-widest">{{ log.target_type }}</div>
                  <div class="text-xs font-bold text-slate-500">{{ log.target_name }}</div>
                </div>
              </td>
              <td class="px-8 py-5">
                <div class="text-[11px] text-slate-600 font-medium leading-relaxed max-w-md">
                   {{ formatDetails(log.details) }}
                </div>
              </td>
            </tr>
            <tr v-if="!loading && filteredLogs.length === 0">
              <td colspan="5" class="p-12 text-center text-gray-400 italic font-bold">No activity sequences detected.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Search, History, User, Terminal, Info, ChevronLeft, ChevronRight, Activity } from 'lucide-vue-next';


const logs = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchLogs = async () => {
  try {
    const res = await axios.get('/api/activity-logs');

    // Laravel paginator returns { data: [...], ...meta }
    logs.value = res.data.data || res.data;
  } catch (err) {
    console.error('Error fetching logs:', err);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const getActionClass = (action) => {
  const a = action?.toLowerCase() || '';

  if (a.includes('create')) return 'bg-green-100 text-green-700';
  if (a.includes('delete')) return 'bg-red-100 text-red-700';
  if (a.includes('update')) return 'bg-blue-100 text-blue-700';

  return 'bg-gray-100 text-gray-700';
};

const formatDetails = (text) => {
  if (!text) return '';

  return text
    .replace(/\s*\(ID:\s*\d+\)/gi, '')
    .replace(/#\d+/g, '')
    .replace(/\s\s+/g, ' ')
    .trim();
};

/**
 * Search across all columns
 */
const filteredLogs = computed(() => {
  if (!searchQuery.value) return logs.value;

  const query = searchQuery.value.toLowerCase();

  return logs.value.filter((log) => {
    const values = [
      formatDate(log.created_at),
      log.user?.name || log.user_name || 'System',
      log.action,
      log.target_type,
      log.target_name,
      formatDetails(log.details),
    ];

    return values.some((value) =>
      String(value || '').toLowerCase().includes(query)
    );
  });
});

onMounted(fetchLogs);
</script>
