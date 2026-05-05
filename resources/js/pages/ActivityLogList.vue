<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Activity Logs</h1>
      <p class="text-sm text-gray-500">Track all changes and movements across the system.</p>
    </div>

    <!-- Search Bar -->
    <div class="mb-4 flex items-center">
      <input
        type="text"
        v-model="searchQuery"
        @keyup.enter="fetchLogs"
        placeholder="Search logs..."
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
    </div>

    <div class="bg-white border-t-[3px] border-gray-400 rounded shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
              <th class="p-3 border-r w-48">Date & Time</th>
              <th class="p-3 border-r w-40">User</th>
              <th class="p-3 border-r w-32 text-center">Action</th>
              <th class="p-3 border-r w-40">Module</th>
              <th class="p-3 border-r w-48">Target</th>
              <th class="p-3">Details</th>
            </tr>
          </thead>
          <tbody class="text-[13px]">
            <tr v-if="loading">
              <td colspan="6" class="p-6">
                <Loader />
              </td>
            </tr>

            <tr v-for="log in logs" :key="log.id" class="border-b hover:bg-gray-50 transition-colors">
              <td class="p-3 border-r text-gray-500">{{ formatDate(log.created_at) }}</td>
              <td class="p-3 border-r font-medium text-gray-700">
                {{ log.user?.name || log.user_name || 'System' }}
              </td>
              <td class="p-3 border-r text-center">
                <span :class="getActionClass(log.action)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                  {{ log.action }}
                </span>
              </td>
              <td class="p-3 border-r font-semibold text-[#3c8dbc]">{{ log.target_type }}</td>
              <td class="p-3 border-r font-medium text-gray-800">{{ log.target_name }}</td>
              <td class="p-3 text-gray-600">
                {{ formatDetails(log.details) }}
              </td>
            </tr>

            <tr v-if="!loading && logs.length === 0">
              <td colspan="6" class="p-6 text-center text-gray-500">No activity recorded yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const logs = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const fetchLogs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/activity-logs', {
      params: {
        search: searchQuery.value,
        limit: 50,
        page: page
      }
    });
    // Correctly point to the data array inside the pagination object
    logs.value = res.data.data || [];
    pagination.value.current_page = res.data.current_page;
    pagination.value.last_page = res.data.last_page;
    pagination.value.total = res.data.total;
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
    .replace(/\bID:\s*\d+/gi, '')
    .replace(/#\d+/g, '')
    .replace(/\b\d+\b/g, '')
    .replace(/\s\s+/g, ' ')
    .trim();
};

onMounted(() => fetchLogs(1));
</script>

<style scoped>
/* Add a simple pagination footer if needed */
.pagination-trigger {
  @apply px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors;
}
</style>