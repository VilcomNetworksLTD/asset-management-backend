<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const filters = reactive({ search: '', status_id: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ Asset_ID: '', Ticket_ID: '', Request_Date: '', Completion_Date: '', Maintenance_Type: '', Description: '', Cost: '', Status_ID: '1', Maintenance_Date: '' })
const assetStatusId = ref('')
const statuses = ref([])

const loadStatuses = async () => {
  const { data } = await axios.get('/api/assets')
  const all = (data || []).map(a => a.status).filter(Boolean)
  const dedup = new Map()
  for (const s of all) {
    if (!dedup.has(s.id)) dedup.set(s.id, s)
  }
  statuses.value = Array.from(dedup.values())
}

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/maintenances/list', {
      params: {
        search: filters.search || undefined,
        status_id: filters.status_id || undefined,
        per_page: filters.per_page,
        page
      }
    })
    rows.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { Asset_ID: '', Ticket_ID: '', Request_Date: '', Completion_Date: '', Maintenance_Type: '', Description: '', Cost: '', Status_ID: '1', Maintenance_Date: '' })
  assetStatusId.value = ''
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    Asset_ID: row.Asset_ID,
    Ticket_ID: row.Ticket_ID || '',
    Request_Date: row.Request_Date?.slice(0, 16) || '',
    Completion_Date: row.Completion_Date?.slice(0, 16) || '',
    Maintenance_Type: row.Maintenance_Type || '',
    Description: row.Description || '',
    Cost: row.Cost || '',
    Status_ID: row.Status_ID || '1',
    Maintenance_Date: row.Maintenance_Date?.slice(0, 16) || ''
  })
  assetStatusId.value = row.asset?.Status_ID ? String(row.asset.Status_ID) : ''
  showForm.value = true
}

const save = async () => {
  saving.value = true
  const payload = {
    ...form,
    Asset_ID: Number(form.Asset_ID),
    Ticket_ID: form.Ticket_ID ? Number(form.Ticket_ID) : null,
    Status_ID: Number(form.Status_ID),
    Cost: form.Cost === '' ? null : Number(form.Cost),
    asset_status_id: assetStatusId.value ? Number(assetStatusId.value) : null,
  }
  try {
    if (editingId.value) await axios.put(`/api/maintenances/${editingId.value}`, payload)
    else await axios.post('/api/maintenances', payload)
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this maintenance record?')) return
  await axios.delete(`/api/maintenances/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(async () => {
  await Promise.all([fetchRows(), loadStatuses()])
})
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Maintenance History</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9] text-sm font-medium transition-colors">Schedule Maintenance</button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm" placeholder="Search type/description" />
      <input v-model="filters.status_id" type="number" class="border px-3 py-2 rounded text-sm" placeholder="Status ID" />
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.Asset_ID" type="number" class="border p-2 rounded" placeholder="Asset ID" />
      <input v-model="form.Ticket_ID" type="number" class="border p-2 rounded" placeholder="Ticket ID (optional)" />
      <input v-model="form.Maintenance_Type" class="border p-2 rounded" placeholder="Type" />
      <input v-model="form.Cost" type="number" class="border p-2 rounded" placeholder="Cost" />
      <input v-model="form.Request_Date" type="datetime-local" class="border p-2 rounded" placeholder="Request Date" />
      <input v-model="form.Completion_Date" type="datetime-local" class="border p-2 rounded" placeholder="Completion Date" />
      <input v-model="form.Maintenance_Date" type="datetime-local" class="border p-2 rounded" placeholder="Maintenance Date" />
      <input v-model="form.Status_ID" type="number" class="border p-2 rounded" placeholder="Status ID" />
      <select v-model="assetStatusId" class="border p-2 rounded">
        <option value="">Asset Status (optional)</option>
        <option v-for="s in statuses" :key="s.id" :value="String(s.id)">{{ s.Status_Name }}</option>
      </select>
      <textarea v-model="form.Description" class="border p-2 rounded col-span-2" placeholder="Description"></textarea>
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-[3px] border-[#3c8dbc] rounded shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
            <th class="p-3 border-r w-12 text-center">ID</th><th class="p-3 border-r">Asset</th><th class="p-3 border-r">Type</th><th class="p-3 border-r">Request Date</th><th class="p-3 border-r">Completion</th><th class="p-3 border-r">Cost</th><th class="p-3 border-r">Status</th><th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px]">
          <tr v-if="loading"><td colspan="8" class="p-6 text-center text-gray-500">Loading maintenance records...</td></tr>
          <tr v-for="item in rows" :key="item.id" class="border-b hover:bg-gray-50 transition-colors">
            <td class="p-3 border-r text-center">{{ item.id }}</td>
            <td class="p-3 border-r font-medium text-[#3c8dbc]">{{ item.asset?.Asset_Name || 'Unknown Asset' }}</td>
            <td class="p-3 border-r">{{ item.Maintenance_Type }}</td>
            <td class="p-3 border-r">{{ item.Request_Date }}</td>
            <td class="p-3 border-r">{{ item.Completion_Date || 'In Progress...' }}</td>
            <td class="p-3 border-r">${{ item.Cost }}</td>
            <td class="p-3 border-r">{{ item.status?.Status_Name || 'N/A' }}</td>
            <td class="p-3 text-center space-x-2"><button class="text-blue-500" @click="openEdit(item)"><i class="fa fa-pencil-alt"></i> Edit</button><button class="text-red-500" @click="removeRow(item.id)"><i class="fa fa-trash"></i> Delete</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2 text-sm">
      <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="px-3 py-1 border rounded">Prev</button>
      <span>Page {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }} records)</span>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="px-3 py-1 border rounded">Next</button>
    </div>
  </div>
</template>