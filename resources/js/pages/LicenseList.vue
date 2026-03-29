<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';

const rows = ref([])
const users = ref([])
const departments = ref([]) // For the department dropdown
const loading = ref(false)
const saving = ref(false)

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  const curr = settings.value?.currency || 'KES';
  return `${curr} ${Number(amount).toLocaleString()}`;
}

const filters = reactive({ search: '', manufacturer: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '',
  product_key: '',
  manufacturer: '',
  total_seats: 1,
  remaining_seats: 1,
  price: '',
  expiry_date: '',
  department_id: '', // Changed from 'departments'
  allocation_type: '',
  renewal_type: ''
})

const showAssignForm = ref(false)
const assignForm = reactive({ item: null, user_id: '' })

const manufacturerOptions = computed(() => [...new Set((rows.value || []).map(r => r.manufacturer).filter(Boolean))])

const isFocused = useWindowFocus()

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

const loadUsers = async () => {
  try {
    const { data } = await axios.get('/api/users')
    users.value = data || []
  } catch (e) {
    console.error('Failed to load users', e)
  }
}

const loadDepartments = async () => {
  try {
    const { data } = await axios.get('/api/departments')
    departments.value = data || []
  } catch (e) {
    console.error('Failed to load departments', e)
  }
}

/* ================= LOAD DATA ================= */
const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/licenses/list', {
      params: {
        search: filters.search || undefined,
        manufacturer: filters.manufacturer || undefined,
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
    name: '',
    product_key: '',
    manufacturer: '',
    total_seats: 1,
    remaining_seats: 1,
    price: '',
    expiry_date: '',
    department_id: '',
    allocation_type: '',
    renewal_type: ''
  })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name || '',
    product_key: row.product_key || '',
    manufacturer: row.manufacturer || '',
    total_seats: row.total_seats || 1,
    remaining_seats: row.remaining_seats || 1,
    price: row.price || '',
    expiry_date: row.expiry_date || '',
    department_id: row.department_id || '',
    allocation_type: row.allocation_type || '',
    renewal_type: row.renewal_type || ''
  })
  showForm.value = true
}

const openAssign = (item) => {
  assignForm.item = item
  assignForm.user_id = ''
  showAssignForm.value = true
}

const submitAssignment = async () => {
  if (!assignForm.item || !assignForm.user_id) {
    return alert('Please select a user.')
  }
  saving.value = true
  try {
    await axios.post(`/api/licenses/${assignForm.item.id}/assign`, { user_id: assignForm.user_id })
    showAssignForm.value = false
    fetchRows(pagination.current_page)
  } catch (e) {
    alert('Failed to assign license: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

const save = async () => {
  saving.value = true
  const payload = {
    ...form,
    total_seats: Number(form.total_seats),
    remaining_seats: Number(form.remaining_seats),
    price: form.price === '' ? null : Number(form.price),
    expiry_date: form.expiry_date || null,
    department_id: form.department_id || null,
    allocation_type: form.allocation_type || null,
    renewal_type: form.renewal_type || null,
  }

  try {
    if (editingId.value) await axios.put(`/api/licenses/${editingId.value}`, payload)
    else await axios.post('/api/licenses', payload)

    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this license?')) return
  await axios.delete(`/api/licenses/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(() => {
  fetchRows()
  loadUsers()
  loadDepartments()
})
</script>

<template>
<div class="p-6">

  <div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Licenses</h1>
    <button @click="openCreate" class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow flex items-center gap-2 text-sm font-medium transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 2a2 2 0 0 0-2 2v5H4a2 2 0 0 0-2 2v2c0 1.1.9 2 2 2h5v5c0 1.1.9 2 2 2h2a2 2 0 0 0 2-2v-5h5a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-5V4a2 2 0 0 0-2-2h-2z"></path></svg>
      New License
    </button>
  </div>

  <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
    <input v-model="filters.search" @keyup.enter="fetchRows(1)" placeholder="Search name/key/manufacturer" class="border px-3 py-2 rounded text-sm focus:ring-2 focus:ring-blue-400 outline-none" />
    <select v-model="filters.manufacturer" class="border px-3 py-2 rounded text-sm">
      <option value="">All manufacturers</option>
      <option v-for="m in manufacturerOptions" :key="m" :value="m">{{ m }}</option>
    </select>
    <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
      <option :value="10">10 per page</option>
      <option :value="20">20 per page</option>
      <option :value="50">50 per page</option>
    </select>
    <button @click="fetchRows(1)" class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm transition-colors">Apply</button>
  </div>

  <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2 border-t-4 border-[#3c8dbc]">
    <input v-model="form.name" class="border p-2 rounded" placeholder="Software Name" />
    <input v-model="form.product_key" class="border p-2 rounded" placeholder="Product Key" />
    <input v-model="form.total_seats" type="number" class="border p-2 rounded" placeholder="Total Seats" />
    <input v-model="form.remaining_seats" type="number" class="border p-2 rounded" placeholder="Remaining Seats" />
    <input v-model="form.expiry_date" type="date" class="border p-2 rounded" placeholder="Expiry Date" />
    <select v-model="form.department_id" class="border p-2 rounded">
      <option value="">Select Department</option>
      <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
    </select>

    <select v-model="form.allocation_type" class="border p-2 rounded">
      <option value="">Select Allocation Type</option>
      <option value="Per User">Per User</option>
      <option value="Per Device">Per Device</option>
      <option value="Per Processor">Per Processor</option>
      <option value="Site License">Site License</option>
    </select>

    <select v-model="form.renewal_type" class="border p-2 rounded">
      <option value="">Select Renewal Type</option>
      <option value="Annual">Annual</option>
      <option value="Perpetual">Perpetual</option>
      <option value="Monthly">Monthly</option>
      <option value="Trial">Trial</option>
    </select>

    <input v-model="form.price" type="number" class="border p-2 rounded" placeholder="Price" />

    <div class="col-span-2 flex gap-2 pt-2">
      <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 transition-colors">
        {{ editingId ? 'Update' : 'Create' }}
      </button>
      <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors">Cancel</button>
    </div>
  </div>

  <div v-if="showAssignForm" class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="relative p-6 border w-96 shadow-2xl rounded-lg bg-white">
      <div class="text-center">
        <h3 class="text-lg font-bold text-gray-900 border-b pb-2">Assign License</h3>
        <p class="text-sm text-[#3c8dbc] mt-2 font-medium">{{ assignForm.item?.name }}</p>
        
        <div class="mt-4 text-left">
          <label class="text-xs font-bold uppercase text-gray-500">Assign to User</label>
          <select v-model="assignForm.user_id" class="w-full border p-2 rounded mt-1 focus:ring-2 focus:ring-green-500 outline-none">
            <option value="" disabled>Select a user</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
          <p class="text-[11px] text-gray-500 mt-2">Remaining seats: <span class="font-bold">{{ assignForm.item?.remaining_seats }}</span></p>
        </div>

        <div class="flex flex-col gap-2 mt-6">
          <button @click="submitAssignment" :disabled="saving || assignForm.item?.remaining_seats < 1" class="px-4 py-2 bg-green-600 text-white font-medium rounded-md shadow hover:bg-green-700 disabled:opacity-50 transition-colors">
            {{ saving ? 'Assigning...' : 'Confirm Assignment' }}
          </button>
          <button @click="showAssignForm = false" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white border-t-[3px] border-[#3c8dbc] rounded shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
          <th class="p-3 border-r">Software Name</th>
          <th class="p-3 border-r">Product Key</th>
          <th class="p-3 border-r text-center">Seats</th>
          <th class="p-3 border-r">Expiry Date</th>
          <th class="p-3 border-r">Departments</th>
          <th class="p-3 border-r">Allocation</th>
          <th class="p-3 border-r">Renewal</th>
          <th class="p-3 border-r text-right">Price</th>
          <th class="p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="text-[13px] divide-y">
        <tr v-if="loading">
          <td colspan="9" class="p-6 text-center text-gray-500 italic">Loading licenses...</td>
        </tr>
        <tr v-for="license in rows" :key="license.id" class="hover:bg-gray-50 transition-colors">
          <td class="p-3 border-r font-medium text-[#3c8dbc]">{{ license.name }}</td>
          <td class="p-3 border-r font-mono text-xs text-gray-500">{{ license.product_key || 'N/A' }}</td>
          <td class="p-3 border-r text-center">
            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-[11px] font-bold">
              {{ license.remaining_seats }} / {{ license.total_seats }}
            </span>
          </td>
          <td class="p-3 border-r">{{ license.expiry_date || '-' }}</td>
          <td class="p-3 border-r text-xs">{{ license.department?.name || '-' }}</td>
          <td class="p-3 border-r">{{ license.allocation_type || '-' }}</td>
          <td class="p-3 border-r">{{ license.renewal_type || '-' }}</td>
          <td class="p-3 border-r text-right font-medium">{{ license.price ? formatMoney(license.price) : '-' }}</td>
          <td class="p-3">
            <div class="flex items-center justify-center gap-4">
              <button 
                class="text-green-600 hover:text-green-800 disabled:opacity-30 transition-colors" 
                @click="openAssign(license)" 
                :disabled="license.remaining_seats < 1"
                title="Assign User"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
              </button>

              <button 
                class="text-blue-500 hover:text-blue-700 transition-colors" 
                @click="openEdit(license)"
                title="Edit License"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              </button>

              <button 
                class="text-red-500 hover:text-red-700 transition-colors" 
                @click="removeRow(license.id)"
                title="Delete License"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
    <div class="flex items-center gap-2">
      <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50">Prev</button>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50">Next</button>
    </div>
    <span>Page <b>{{ pagination.current_page }}</b> of {{ pagination.last_page }} <span class="mx-2 text-gray-300">|</span> {{ pagination.total }} records total</span>
  </div>

</div>
</template>