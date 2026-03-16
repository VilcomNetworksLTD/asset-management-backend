<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  const curr = settings.value?.currency || 'KES';
  return `${curr} ${Number(amount).toLocaleString()}`;
}

const filters = reactive({ search: '', status_id: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)

const form = reactive({
  Asset_ID: '',
  Request_Date: '',
  Completion_Date: '',
  Maintenance_Type: '',
  Description: '',
  Cost: '',
  Status_ID: '',
  
})

const statuses = ref([])
const assets = ref([])

const isFocused = useWindowFocus()

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

/* ================= LOAD DATA ================= */

const loadAssets = async () => {
  const { data } = await axios.get('/api/assets')
  assets.value = data || []
}

const loadStatuses = async () => {
  const { data } = await axios.get('/api/statuses')
  statuses.value = data || []
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

/* ================= FORM ACTIONS ================= */

const openCreate = () => {
  editingId.value = null
  Object.assign(form, {
    Asset_ID: '',
    Request_Date: '',
    Completion_Date: '',
    Maintenance_Type: '',
    Description: '',
    Cost: '',
    Status_ID: '',
    
  })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    Asset_ID: row.Asset_ID,
    Request_Date: row.Request_Date?.slice(0, 16) || '',
    Completion_Date: row.Completion_Date?.slice(0, 16) || '',
    Maintenance_Type: row.Maintenance_Type || '',
    Description: row.Description || '',
    Cost: row.Cost || '',
    Status_ID: row.Status_ID || '',
    
  })
  showForm.value = true
}

const save = async () => {
  saving.value = true

  const payload = {
    ...form,
    Asset_ID: Number(form.Asset_ID),
    Status_ID: Number(form.Status_ID),
    Cost: form.Cost === '' ? null : Number(form.Cost)
  }
  console.log("PAYLOAD BEING SENT:", payload)

  try {
    if (editingId.value) {
      await axios.put(`/api/maintenances/${editingId.value}`, payload)
    } else {
      await axios.post('/api/maintenances', payload)
    }

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
  await Promise.all([
    fetchRows(),
    loadStatuses(),
    loadAssets()
  ])
})
</script>

<template>
  <div class="p-6">

    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">
        Maintenance History
      </h1>

      <button
        @click="openCreate"
        class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow flex items-center gap-2 text-sm font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
          <line x1="12" y1="14" x2="12" y2="18"></line>
          <line x1="10" y1="16" x2="14" y2="16"></line>
        </svg>
        Schedule Maintenance
      </button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
      <input
        v-model="filters.search"
        @keyup.enter="fetchRows(1)"
        class="border px-3 py-2 rounded text-sm focus:ring-2 focus:ring-blue-400 outline-none"
        placeholder="Search type/description..."
      />

      <select
        v-model="filters.status_id"
        class="border px-3 py-2 rounded text-sm">
        <option value="">All Statuses</option>
        <option v-for="s in statuses" :key="s.id" :value="s.id">
          {{ s.Status_Name }}
        </option>
      </select>

      <select
        v-model.number="filters.per_page"
        class="border px-3 py-2 rounded text-sm">
        <option :value="10">10 per page</option>
        <option :value="20">20 per page</option>
        <option :value="50">50 per page</option>
      </select>

      <button
        @click="fetchRows(1)"
        class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm transition-colors">
        Apply
      </button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-md border-t-4 border-[#3c8dbc] mb-4 grid grid-cols-2 gap-3">
      
      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Asset</label>
        <select v-model="form.Asset_ID" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none">
          <option value="">Select Asset</option>
          <option v-for="asset in assets" :key="asset.id" :value="asset.id">
            {{ asset.Asset_Name }}
          </option>
        </select>
      </div>

      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Status</label>
        <select v-model="form.Status_ID" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none">
          <option value="">Select Status</option>
          <option v-for="s in statuses" :key="s.id" :value="s.id">
            {{ s.Status_Name }}
          </option>
        </select>
      </div>

      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Maintenance Type</label>
        <select v-model="form.Maintenance_Type" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none">
          <option value="">Select Type</option>
          <option value="Preventive">Preventive</option>
          <option value="Corrective">Corrective</option>
          <option value="Inspection">Inspection</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Cost</label>
        <input v-model="form.Cost" type="number" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="0.00" />
      </div>

      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Request Date</label>
        <input
          v-model="form.Request_Date"
          type="datetime-local"
          class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none"
        />
      </div>

      <div class="flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Completion Date</label>
        <input
          v-model="form.Completion_Date"
          type="datetime-local"
          class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none"
        />
      </div>

      <div class="col-span-2 flex flex-col">
        <label class="text-xs font-bold text-gray-500 mb-1">Description</label>
        <textarea
          v-model="form.Description"
          class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none"
          placeholder="Details about the maintenance work...">
        </textarea>
      </div>

      <div class="col-span-2 flex gap-2 pt-2">
        <button
          :disabled="saving"
          @click="save"
          class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 transition-colors">
          {{ editingId ? 'Update Record' : 'Create Record' }}
        </button>

        <button
          @click="showForm = false"
          class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors">
          Cancel
        </button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#3c8dbc] rounded shadow-md overflow-hidden">
      <table class="w-full text-left border-collapse">

        <thead>
          <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
            <th class="p-4">Asset</th>
            <th class="p-4">Type</th>
            <th class="p-4">Request Date</th>
            <th class="p-4">Completion</th>
            <th class="p-4 text-right">Cost</th>
            <th class="p-4 text-center">Status</th>
            <th class="p-4 text-center">Actions</th>
          </tr>
        </thead>

        <tbody class="text-[13px] divide-y">
          <tr v-if="loading">
            <td colspan="7" class="p-8 text-center text-gray-500 italic">
              Loading maintenance records...
            </td>
          </tr>

          <tr
            v-for="item in rows"
            :key="item.id"
            class="hover:bg-gray-50 transition-colors">

            <td class="p-4 font-bold text-[#3c8dbc]">
              {{ item.asset?.Asset_Name || 'Unknown Asset' }}
            </td>

            <td class="p-4">
              <span class="px-2 py-0.5 rounded bg-gray-100 text-[11px] font-medium text-gray-600">
                {{ item.Maintenance_Type }}
              </span>
            </td>

            <td class="p-4 text-gray-600">
              {{ item.Request_Date }}
            </td>

            <td class="p-4 text-gray-600">
              {{ item.Completion_Date || 'In Progress...' }}
            </td>

            <td class="p-4 text-right font-medium">
              {{ item.Cost ? formatMoney(item.Cost) : '-' }}
            </td>

            <td class="p-4 text-center">
              <span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#3c8dbc] text-[10px] font-bold border border-blue-100">
                {{ item.status?.Status_Name || 'N/A' }}
              </span>
            </td>

            <td class="p-4">
              <div class="flex items-center justify-center gap-4">
                <button 
                  class="text-blue-500 hover:text-blue-700 transition-colors" 
                  @click="openEdit(item)"
                  title="Edit Maintenance"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>

                <button 
                  class="text-red-500 hover:text-red-700 transition-colors" 
                  @click="removeRow(item.id)"
                  title="Delete Record"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <div class="flex items-center gap-2">
        <button 
          :disabled="pagination.current_page <= 1" 
          @click="fetchRows(pagination.current_page - 1)" 
          class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
          Prev
        </button>
        <button 
          :disabled="pagination.current_page >= pagination.last_page" 
          @click="fetchRows(pagination.current_page + 1)" 
          class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">
          Next
        </button>
      </div>
      <span>Page <b>{{ pagination.current_page }}</b> of {{ pagination.last_page }} <span class="mx-2 text-gray-300">|</span> {{ pagination.total }} records</span>
    </div>

  </div>
</template>