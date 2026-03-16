<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-2xl font-semibold text-gray-800 mb-4">Support Tickets</h1>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50">
          <nav class="flex -mb-px" aria-label="Tabs">
            <button @click="currentTab = 'asset'" :class="tabClass('asset')">
              Asset Tickets
            </button>
            <button @click="currentTab = 'general'" :class="tabClass('general')">
              General / IT Support Tickets
            </button>
          </nav>
        </div>

        <div v-if="role === 'admin'" class="p-4 bg-white border-b flex gap-2 flex-wrap items-center">
          <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm focus:ring-blue-400 outline-none" placeholder="Search..." />
          <select v-model="filters.priority" class="border px-3 py-2 rounded text-sm">
            <option value="">All priorities</option>
            <option value="low">low</option>
            <option value="medium">medium</option>
            <option value="high">high</option>
          </select>
          
          <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
          <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm hover:bg-black transition-colors">Apply</button>
        </div>

        <div v-if="showUpdate" class="bg-gray-50 p-4 border-b grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="text-xs font-bold text-gray-500 mb-1 block">Description</label>
            <textarea v-model="updateForm.description" rows="3" class="border p-2 rounded w-full" placeholder="Ticket description..."></textarea>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 mb-1 block">Priority</label>
            <select v-model="updateForm.priority" class="border p-2 rounded w-full">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
          <!-- status is now handled automatically by the server; no manual input -->
          <div class="col-span-2">
            <textarea v-model="updateForm.communication" class="border p-2 rounded w-full" placeholder="Add a new communication note..."></textarea>
          </div>
          <div class="col-span-2 flex gap-2">
            <button :disabled="saving" @click="saveUpdate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
            <button @click="showUpdate = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
          </div>
        </div>

        <div v-if="showAssign" class="bg-gray-50 p-4 border-b grid grid-cols-2 gap-3">
          <select v-model="assignForm.asset_id" class="border p-2 rounded col-span-2">
            <option value="" disabled>Select asset to assign</option>
            <option v-for="asset in assignOptions" :key="asset.id" :value="asset.id">
              {{ asset.Asset_Name }} ({{ asset.Serial_No || 'No Serial' }}) - {{ asset.status?.Status_Name || 'N/A' }}
            </option>
          </select>

          <div class="col-span-2 border rounded p-2 bg-white">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold uppercase text-gray-500">Accessories (Optional)</p>
              <button type="button" @click="addAccessoryRow" class="text-xs px-2 py-1 border rounded hover:bg-gray-100">+ Add</button>
            </div>
            <div v-for="(row, idx) in assignForm.accessory_allocations" :key="`acc-${idx}`" class="grid grid-cols-12 gap-2 mb-2">
              <select v-model="row.id" class="border p-2 rounded col-span-8">
                <option value="" disabled>Select accessory</option>
                <option v-for="item in accessoryOptions" :key="item.id" :value="item.id">
                  {{ item.name }} (Stock: {{ item.remaining_qty }})
                </option>
              </select>
              <input v-model.number="row.qty" type="number" min="1" class="border p-2 rounded col-span-3" placeholder="Qty" />
              <button type="button" @click="removeAccessoryRow(idx)" class="col-span-1 text-red-600 font-bold text-lg hover:text-red-800">×</button>
            </div>
          </div>

          <div class="col-span-2 border rounded p-2 bg-white">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold uppercase text-gray-500">Consumables (Optional)</p>
              <button type="button" @click="addConsumableRow" class="text-xs px-2 py-1 border rounded hover:bg-gray-100">+ Add</button>
            </div>
            <div v-for="(row, idx) in assignForm.consumable_allocations" :key="`con-${idx}`" class="grid grid-cols-12 gap-2 mb-2">
              <select v-model="row.id" class="border p-2 rounded col-span-8">
                <option value="" disabled>Select consumable</option>
                <option v-for="item in consumableOptions" :key="item.id" :value="item.id">
                  {{ item.item_name }} (Stock: {{ item.in_stock }})
                </option>
              </select>
              <input v-model.number="row.qty" type="number" min="1" class="border p-2 rounded col-span-3" placeholder="Qty" />
              <button type="button" @click="removeConsumableRow(idx)" class="col-span-1 text-red-600 font-bold text-lg hover:text-red-800">×</button>
            </div>
          </div>

          <textarea v-model="assignForm.communication" class="border p-2 rounded col-span-2" placeholder="Assignment note"></textarea>
          <div class="col-span-2 flex gap-2">
            <button @click="submitAssign" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Assign</button>
            <button @click="showAssign = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
              <tr class="text-[11px] uppercase text-gray-500 font-bold">
                <th v-if="role === 'admin'" class="p-4">Employee</th>
                <th class="p-4">{{ currentTab === 'asset' ? 'Asset & Issue' : 'Subject & Description' }}</th>
                <th class="p-4">Priority</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y text-sm">
              <tr v-if="loading">
                <td :colspan="role === 'admin' ? 5 : 4" class="p-8 text-center text-gray-400">Loading...</td>
              </tr>
              <tr v-for="ticket in rows" :key="ticket.id" class="hover:bg-gray-50">
                <td v-if="role === 'admin'" class="p-4">
                  <div class="font-medium">{{ ticket.user?.name || 'Unknown' }}</div>
                </td>

                <td class="p-4">
                  <div :class="role !== 'admin' ? 'bg-yellow-50 p-3 rounded-lg' : ''">
                    <div class="font-bold text-gray-700 mb-1">
                      {{ currentTab === 'asset' ? (ticket.issue?.asset?.Asset_Name || 'Asset Request') : extractSubject(ticket.Description) }}
                    </div>
                    <div class="text-xs text-gray-500 italic max-w-xs whitespace-pre-line break-words" :title="ticket.Description">
                    {{ ticket.Description }}
                  </div>
                  </div>
                </td>
                <td class="p-4"><span :class="priorityClass(ticket.Priority)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">{{ ticket.Priority }}</span></td>
                <td class="p-4"><span class="text-gray-600">{{ ticket.status?.Status_Name || 'Pending' }}</span></td>
                <td class="p-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button @click="openUpdate(ticket)" class="p-1 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Update Log">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <button v-if="role === 'admin' && currentTab === 'general'" @click="resolveTicket(ticket)" class="p-1 text-teal-600 hover:bg-teal-50 rounded transition-colors" title="Mark as Resolved">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>

                    <button v-if="role === 'admin' && currentTab === 'asset' && !ticket.issue?.asset" @click="openAssign(ticket)" class="p-1 text-green-600 hover:bg-green-50 rounded transition-colors" title="Assign Asset">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      </svg>
                    </button>

                    <button v-if="role === 'admin'" @click="removeRow(ticket.id)" class="p-1 text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && rows.length === 0">
                <td :colspan="role === 'admin' ? 5 : 4" class="p-8 text-center text-gray-400">No {{ currentTab }} tickets found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="role === 'admin' && pagination.last_page > 1" class="p-4 border-t flex items-center justify-between text-sm">
          <div>
            Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} records)
          </div>
          <div class="flex items-center gap-2">
            <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="p-2 border rounded bg-white hover:bg-gray-50 disabled:opacity-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="p-2 border rounded bg-white hover:bg-gray-50 disabled:opacity-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import axios from 'axios';

const all_rows = ref([])
const loading = ref(false);
const saving = ref(false);
const currentTab = ref('asset');

// Removed status_id from filters
const filters = reactive({ search: '', priority: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showUpdate = ref(false)
const editingId = ref(null)
const updateForm = reactive({ description: '', priority: 'medium', communication: '' })
const showAssign = ref(false)
const assignTicket = ref(null)
const assignForm = reactive({ asset_id: '', communication: '', accessory_allocations: [], consumable_allocations: [] })
const assignOptions = ref([])
const accessoryOptions = ref([])
const consumableOptions = ref([])
const statuses = ref([])

const role = (() => {
  try { return JSON.parse(localStorage.getItem('user_data') || '{}').role || 'user' } catch { return 'user' }
})()

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    if (role === 'admin') {
      const { data } = await axios.get('/api/tickets/list', {
        params: {
          search: filters.search || undefined,
          priority: filters.priority || undefined,
          // status_id removed from params
          per_page: filters.per_page,
          page
        }
      })
      all_rows.value = data.data || []
      pagination.current_page = data.current_page || 1
      pagination.last_page = data.last_page || 1
      pagination.total = data.total || 0
    } else {
      const { data } = await axios.get('/api/my-tickets')
      all_rows.value = data || []
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = all_rows.value.length
    }
  } finally {
    loading.value = false
  }
}

const loadStatuses = async () => {
  try {
    const { data } = await axios.get('/api/statuses');
    statuses.value = data || [];
  } catch (e) {
    console.error('Failed to load statuses', e);
  }
};

const isAssetTicket = (ticket) => {
  const desc = ticket.Description || '';
  return !!(ticket.issue || desc.includes('Request Category:'));
};

const rows = computed(() => {
  if (currentTab.value === 'asset') {
    return all_rows.value.filter(t => isAssetTicket(t));
  } else { // general
    return all_rows.value.filter(t => !isAssetTicket(t));
  }
});

const extractSubject = (desc) => {
  if (!desc) return 'No Subject';
  const lines = desc.split('\n');
  const subjectLine = lines.find(l => l.startsWith('Subject:'));
  if (subjectLine) {
    return subjectLine.split(':')[1].trim();
  }
  return 'General Inquiry';
};

const tabClass = (tabName) => [
  'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm sm:text-base transition-colors',
  currentTab.value === tabName
    ? 'border-blue-500 text-blue-600'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
];

const openUpdate = (ticket) => {
  editingId.value = ticket.id
  updateForm.description = ticket.Description || ''
  updateForm.priority = ticket.Priority || 'medium'
  updateForm.communication = '' 
  showUpdate.value = true
}

const resolveTicket = async (ticket) => {
  if (!confirm('Mark this general ticket as resolved?')) return;
  try {
    await axios.put(`/api/tickets/${ticket.id}`, {
      action: 'resolve',
      communication: 'Issue has been resolved by IT Support.'
    });
    alert('Ticket marked as resolved.');
    fetchRows(pagination.current_page);
    eventBus.emit('ticket-changed');
  } catch (err) {
    alert('Failed to resolve ticket.');
  }
}

const saveUpdate = async () => {
  if (!editingId.value) return;
  saving.value = true
  try {
    await axios.put(`/api/tickets/${editingId.value}`, {
      description: updateForm.description,
      priority: updateForm.priority,
      communication: updateForm.communication
    })
    showUpdate.value = false
    editingId.value = null
    fetchRows(pagination.current_page)
    eventBus.emit('ticket-changed');
  } catch (err) {
    console.error("Update failed", err);
  } finally {
    saving.value = false
  }
}

const extractRequestedCategory = (ticket) => {
  const text = String(ticket?.Description || '')
  const line = text.split('\n').find((l) => l.toLowerCase().startsWith('request category:'))
  return line ? line.split(':').slice(1).join(':').trim() : ''
}

const openAssign = async (ticket) => {
  assignTicket.value = ticket
  assignForm.asset_id = ''
  assignForm.communication = ''
  assignForm.accessory_allocations = []
  assignForm.consumable_allocations = []
  showAssign.value = true

  const category = extractRequestedCategory(ticket)
  const { data } = await axios.get('/api/assets/list', {
    params: { category: category || undefined, per_page: 100, status_id: undefined }
  })

  const rows = data?.data || []
  assignOptions.value = rows.filter((a) => {
    const status = String(a?.status?.Status_Name || '').toLowerCase()
    return status.includes('ready') || status.includes('available')
  })

  const [accessoryRes, consumableRes] = await Promise.all([
    axios.get('/api/accessories/list', { params: { per_page: 100 } }),
    axios.get('/api/consumables/list', { params: { per_page: 100 } }),
  ])

  accessoryOptions.value = (accessoryRes?.data?.data || []).filter((a) => Number(a.remaining_qty) > 0)
  consumableOptions.value = (consumableRes?.data?.data || []).filter((c) => Number(c.in_stock) > 0)
}

const addAccessoryRow = () => { assignForm.accessory_allocations.push({ id: '', qty: 1 }) }
const removeAccessoryRow = (index) => { assignForm.accessory_allocations.splice(index, 1) }
const addConsumableRow = () => { assignForm.consumable_allocations.push({ id: '', qty: 1 }) }
const removeConsumableRow = (index) => { assignForm.consumable_allocations.splice(index, 1) }

const submitAssign = async () => {
  if (!assignTicket.value) return
  await axios.post(`/api/tickets/${assignTicket.value.id}/assign-asset`, {
    asset_id: Number(assignForm.asset_id),
    communication: assignForm.communication || 'Asset assigned by admin',
    accessory_allocations: assignForm.accessory_allocations
      .filter((x) => x.id && Number(x.qty) > 0)
      .map((x) => ({ id: Number(x.id), qty: Number(x.qty) })),
    consumable_allocations: assignForm.consumable_allocations
      .filter((x) => x.id && Number(x.qty) > 0)
      .map((x) => ({ id: Number(x.id), qty: Number(x.qty) })),
  })
  showAssign.value = false
  fetchRows(pagination.current_page)
}

const removeRow = async (id) => {
  if (role !== 'admin') return
  if (!confirm('Delete this ticket?')) return
  await axios.delete(`/api/tickets/${id}`)
  fetchRows(pagination.current_page)
}

const priorityClass = (p) => {
  const colors = {
    high: 'bg-red-100 text-red-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-green-100 text-green-700'
  }
  return colors[String(p || '').toLowerCase()] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  fetchRows()
  if (role === 'admin') {
    loadStatuses();
  }
})
</script>