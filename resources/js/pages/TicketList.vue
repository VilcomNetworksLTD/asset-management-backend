<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const filters = reactive({ search: '', priority: '', status_id: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showUpdate = ref(false)
const editingId = ref(null)
const updateForm = reactive({ communication: '', status_id: 2 })
const showAssign = ref(false)
const assignTicket = ref(null)
const assignForm = reactive({ asset_id: '', communication: '', accessory_allocations: [], consumable_allocations: [] })
const assignOptions = ref([])
const accessoryOptions = ref([])
const consumableOptions = ref([])

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
          status_id: filters.status_id || undefined,
          per_page: filters.per_page,
          page
        }
      })
      rows.value = data.data || []
      pagination.current_page = data.current_page || 1
      pagination.last_page = data.last_page || 1
      pagination.total = data.total || 0
    } else {
      const { data } = await axios.get('/api/my-tickets')
      rows.value = data || []
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = rows.value.length
    }
  } finally {
    loading.value = false
  }
}

const openUpdate = (ticket) => {
  editingId.value = ticket.id
  updateForm.communication = ticket.Communication_log || ''
  updateForm.status_id = ticket.Status_ID || 2
  showUpdate.value = true
}

const saveUpdate = async () => {
  saving.value = true
  try {
    await axios.put(`/api/tickets/${editingId.value}`, {
      communication: updateForm.communication,
      status_id: Number(updateForm.status_id)
    })
    showUpdate.value = false
    fetchRows(pagination.current_page)
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
    params: {
      category: category || undefined,
      per_page: 100,
      status_id: undefined
    }
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

const addAccessoryRow = () => {
  assignForm.accessory_allocations.push({ id: '', qty: 1 })
}

const removeAccessoryRow = (index) => {
  assignForm.accessory_allocations.splice(index, 1)
}

const addConsumableRow = () => {
  assignForm.consumable_allocations.push({ id: '', qty: 1 })
}

const removeConsumableRow = (index) => {
  assignForm.consumable_allocations.splice(index, 1)
}

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

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Support Tickets</h1>

    <div v-if="role === 'admin'" class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm" placeholder="Search" />
      <select v-model="filters.priority" class="border px-3 py-2 rounded text-sm">
        <option value="">All priorities</option><option value="low">low</option><option value="medium">medium</option><option value="high">high</option>
      </select>
      <input v-model="filters.status_id" type="number" class="border px-3 py-2 rounded text-sm" placeholder="Status ID" />
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showUpdate" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <textarea v-model="updateForm.communication" class="border p-2 rounded col-span-2" placeholder="Communication note"></textarea>
      <input v-model="updateForm.status_id" type="number" class="border p-2 rounded" placeholder="Status ID" />
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="saveUpdate" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
        <button @click="showUpdate = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div v-if="showAssign" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <select v-model="assignForm.asset_id" class="border p-2 rounded col-span-2">
        <option value="" disabled>Select asset to assign</option>
        <option v-for="asset in assignOptions" :key="asset.id" :value="asset.id">
          {{ asset.Asset_Name }} ({{ asset.Serial_No || 'No Serial' }}) - {{ asset.status?.Status_Name || 'N/A' }}
        </option>
      </select>

      <div class="col-span-2 border rounded p-2">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold uppercase text-gray-500">Accessories (Optional)</p>
          <button type="button" @click="addAccessoryRow" class="text-xs px-2 py-1 border rounded">+ Add</button>
        </div>
        <div v-for="(row, idx) in assignForm.accessory_allocations" :key="`acc-${idx}`" class="grid grid-cols-12 gap-2 mb-2">
          <select v-model="row.id" class="border p-2 rounded col-span-8">
            <option value="" disabled>Select accessory</option>
            <option v-for="item in accessoryOptions" :key="item.id" :value="item.id">
              {{ item.name }} (Stock: {{ item.remaining_qty }})
            </option>
          </select>
          <input v-model.number="row.qty" type="number" min="1" class="border p-2 rounded col-span-3" placeholder="Qty" />
          <button type="button" @click="removeAccessoryRow(idx)" class="col-span-1 text-red-600 font-bold">×</button>
        </div>
      </div>

      <div class="col-span-2 border rounded p-2">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold uppercase text-gray-500">Consumables (Optional)</p>
          <button type="button" @click="addConsumableRow" class="text-xs px-2 py-1 border rounded">+ Add</button>
        </div>
        <div v-for="(row, idx) in assignForm.consumable_allocations" :key="`con-${idx}`" class="grid grid-cols-12 gap-2 mb-2">
          <select v-model="row.id" class="border p-2 rounded col-span-8">
            <option value="" disabled>Select consumable</option>
            <option v-for="item in consumableOptions" :key="item.id" :value="item.id">
              {{ item.item_name }} (Stock: {{ item.in_stock }})
            </option>
          </select>
          <input v-model.number="row.qty" type="number" min="1" class="border p-2 rounded col-span-3" placeholder="Qty" />
          <button type="button" @click="removeConsumableRow(idx)" class="col-span-1 text-red-600 font-bold">×</button>
        </div>
      </div>

      <textarea v-model="assignForm.communication" class="border p-2 rounded col-span-2" placeholder="Assignment note"></textarea>
      <div class="col-span-2 flex gap-2">
        <button @click="submitAssign" class="px-4 py-2 bg-green-600 text-white rounded">Assign</button>
        <button @click="showAssign = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white rounded shadow-sm border overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[11px] uppercase text-gray-500 font-bold">
            <th class="p-4">Ticket ID</th>
            <th class="p-4">Employee</th>
            <th class="p-4">Asset & Issue</th>
            <th class="p-4">Priority</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y text-sm">
          <tr v-if="loading"><td colspan="6" class="p-4">Loading...</td></tr>
          <tr v-for="ticket in rows" :key="ticket.id" class="hover:bg-gray-50">
            <td class="p-4 font-mono text-xs">#{{ ticket.id }}</td>
            <td class="p-4"><div class="font-medium">{{ ticket.user?.name || 'Me' }}</div><div class="text-xs text-gray-400">ID: {{ ticket.Employee_ID }}</div></td>
            <td class="p-4"><div class="font-bold text-gray-700">{{ ticket.issue?.asset?.Asset_Name || 'N/A' }}</div><div class="text-xs text-gray-500 italic">"{{ ticket.Description }}"</div></td>
            <td class="p-4"><span :class="priorityClass(ticket.Priority)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">{{ ticket.Priority }}</span></td>
            <td class="p-4"><span class="text-gray-600">{{ ticket.status?.Status_Name || 'Pending' }}</span></td>
            <td class="p-4 text-right">
              <button v-if="role === 'admin'" @click="openUpdate(ticket)" class="text-blue-600 hover:underline font-bold mr-3">Update</button>
              <button v-if="role === 'admin' && !ticket.issue?.asset" @click="openAssign(ticket)" class="text-green-600 hover:underline font-bold mr-3">Assign Asset</button>
              <button v-if="role === 'admin'" @click="removeRow(ticket.id)" class="text-red-600 hover:underline font-bold">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="role === 'admin'" class="mt-3 flex items-center gap-2 text-sm">
      <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="px-3 py-1 border rounded">Prev</button>
      <span>Page {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }} records)</span>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="px-3 py-1 border rounded">Next</button>
    </div>
  </div>
</template>