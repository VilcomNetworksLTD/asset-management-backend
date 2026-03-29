<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';

const rows = ref([])
const users = ref([])
const loading = ref(false)
const saving = ref(false)

// currency helper using settings
const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  const curr = settings.value?.currency || 'KES';
  return `${curr} ${Number(amount).toLocaleString()}`;
}

const filters = reactive({ search: '', category: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', category: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })

const showAssignForm = ref(false)
const assignForm = reactive({ item: null, user_id: '', quantity: 1 })

const categories = computed(() => [...new Set((rows.value || []).map(r => r.category).filter(Boolean))])

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

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/components/list', {
      params: { 
        search: filters.search || undefined, 
        category: filters.category || undefined, 
        per_page: filters.per_page, 
        page 
      }
    })
    // Only show non-deleted components
    rows.value = (data.data || []).filter(r => !r.deleted_at)
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { name: '', category: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, row)
  showForm.value = true
}

const openAssign = (item) => {
  assignForm.item = item
  assignForm.user_id = ''
  assignForm.quantity = 1
  showAssignForm.value = true
}

const submitAssignment = async () => {
  if (!assignForm.item || !assignForm.user_id) {
    return alert('Please select a user.')
  }
  saving.value = true
  try {
    await axios.post(`/api/components/${assignForm.item.id}/assign`, { user_id: assignForm.user_id, quantity: assignForm.quantity })
    showAssignForm.value = false
    fetchRows(pagination.current_page)
  } catch (e) {
    alert('Failed to assign component: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

const save = async () => {
  saving.value = true
  const payload = {
    ...form,
    total_qty: Number(form.total_qty),
    remaining_qty: Number(form.remaining_qty),
    price: form.price === '' ? null : Number(form.price)
  }
  try {
    if (editingId.value) await axios.put(`/api/components/${editingId.value}`, payload)
    else await axios.post('/api/components', payload)
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

// Soft delete implementation
const removeRow = async (id) => {
  if (!confirm('Delete this component?')) return
  await axios.delete(`/api/components/${id}`) // API should handle soft delete
  // Remove from current UI immediately
  rows.value = rows.value.filter(r => r.id !== id)
  fetchRows(pagination.current_page)
}

onMounted(() => {
  fetchRows()
  loadUsers()
})
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Components</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow flex items-center gap-2 text-sm font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Create New
      </button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm focus:ring-2 focus:ring-blue-400 outline-none" placeholder="Search components..." />
      
      <select v-model="filters.category" class="border px-3 py-2 rounded text-sm">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>

      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
        <option :value="10">10 per page</option>
        <option :value="20">20 per page</option>
        <option :value="50">50 per page</option>
      </select>
      <button @click="fetchRows(1)" class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm transition-colors">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-3 border-t-4 border-[#3c8dbc]">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Component Name" />

      <select v-model="form.category" class="border p-2 rounded">
        <option value="">Select Category</option>
        <option value="Motherboard">Motherboard</option>
        <option value="CPU">CPU</option>
        <option value="RAM">RAM</option>
        <option value="GPU">GPU</option>
        <option value="Hard Drive">Hard Drive</option>
        <option value="Power Supply">Power Supply</option>
        <option value="Keyboard">Keyboard</option>
        <option value="Mouse">Mouse</option>
        <option value="Network Card">Network Card</option>
      </select>

      <input v-model="form.serial_no" class="border p-2 rounded" placeholder="Serial No" />
      <input v-model="form.total_qty" type="number" class="border p-2 rounded" placeholder="Total Qty" />
      <input v-model="form.remaining_qty" type="number" class="border p-2 rounded" placeholder="Remaining Qty" />
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
          <h3 class="text-lg font-bold text-gray-900 border-b pb-2">Assign Component</h3>
          <p class="text-sm text-[#3c8dbc] mt-2 font-medium italic">{{ assignForm.item?.name }}</p>
          
          <div class="mt-4 space-y-4 text-left">
            <div>
              <label class="text-xs font-bold uppercase text-gray-500">Assign to User</label>
              <select v-model="assignForm.user_id" class="w-full border p-2 rounded mt-1 focus:ring-2 focus:ring-green-500 outline-none">
                <option value="" disabled>Select a user</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-bold uppercase text-gray-500">Quantity</label>
              <input v-model.number="assignForm.quantity" type="number" min="1" :max="assignForm.item?.remaining_qty" class="w-full border p-2 rounded mt-1 outline-none focus:ring-2 focus:ring-green-500" />
              <p class="text-[11px] text-gray-500 mt-1 italic font-medium">Available in stock: {{ assignForm.item?.remaining_qty }}</p>
            </div>
          </div>

          <div class="flex flex-col gap-2 mt-6">
            <button @click="submitAssignment" :disabled="saving || !assignForm.user_id || assignForm.quantity < 1" class="px-4 py-2 bg-green-600 text-white font-medium rounded-md shadow hover:bg-green-700 disabled:opacity-50 transition-colors">
              {{ saving ? 'Processing...' : 'Confirm Assignment' }}
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
            <th class="p-3 border-r">Component Name</th>
            <th class="p-3 border-r">Category</th>
            <th class="p-3 border-r font-medium">Serial No.</th>
            <th class="p-3 border-r text-center">Availability</th>
            <th class="p-3 border-r text-right">Price</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px] divide-y">
          <tr v-if="loading"><td colspan="6" class="p-8 text-center text-gray-500 italic">Loading components...</td></tr>
          <tr v-for="component in rows" :key="component.id" class="hover:bg-gray-50 transition-colors">
            <td class="p-3 border-r font-medium text-[#3c8dbc]">{{ component.name }}</td>
            <td class="p-3 border-r">
              <span class="text-gray-600">{{ component.category }}</span>
            </td>
            <td class="p-3 border-r font-mono text-[11px] text-gray-500">{{ component.serial_no || 'N/A' }}</td>
            <td class="p-3 border-r text-center">
              <span class="px-2 py-0.5 rounded-full bg-gray-100 text-[11px] font-bold" :class="component.remaining_qty < 1 ? 'text-red-600' : 'text-gray-700'">
                {{ component.remaining_qty }} / {{ component.total_qty }}
              </span>
            </td>
            <td class="p-3 border-r text-right font-medium">{{ formatMoney(component.price) }}</td>
            <td class="p-3">
              <div class="flex items-center justify-center gap-4">
                <button 
                  class="text-green-600 hover:text-green-800 disabled:opacity-30 transition-colors" 
                  @click="openAssign(component)"
                  :disabled="component.remaining_qty < 1"
                  title="Assign to User"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                </button>

                <button 
                  class="text-blue-500 hover:text-blue-700 transition-colors" 
                  @click="openEdit(component)"
                  title="Edit Component"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>

                <button 
                  class="text-red-500 hover:text-red-700 transition-colors" 
                  @click="removeRow(component.id)"
                  title="Delete Component"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>