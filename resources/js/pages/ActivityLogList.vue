```vue
<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Activity Logs</h1>
      <p class="text-sm text-gray-500">Track all changes and movements across the system.</p>
    </div>

    <!-- Search Bar -->
    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search by date, user, action, module, target or details..."
        class="w-full md:w-96 px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-[#3c8dbc]"
      />
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

            <tr
              v-for="log in filteredLogs"
              :key="log.id"
              class="border-b hover:bg-gray-50 transition-colors"
            >
              <td class="p-3 border-r text-gray-500">
                {{ formatDate(log.created_at) }}
              </td>

              <td class="p-3 border-r font-medium text-gray-700">
                {{ log.user?.name || log.user_name || 'System' }}
              </td>

              <td class="p-3 border-r text-center">
                <span
                  :class="getActionClass(log.action)"
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                >
                  {{ log.action }}
                </span>
              </td>

              <td class="p-3 border-r font-semibold text-[#3c8dbc]">
                {{ log.target_type }}
              </td>

              <td class="p-3 border-r font-medium text-gray-800">
                {{ log.target_name }}
              </td>

              <td class="p-3 text-gray-600">
                {{ formatDetails(log.details) }}
              </td>
            </tr>

            <tr v-if="!loading && filteredLogs.length === 0">
              <td colspan="6" class="p-6 text-center text-gray-500">
                No matching activity logs found.
              </td>
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
```
