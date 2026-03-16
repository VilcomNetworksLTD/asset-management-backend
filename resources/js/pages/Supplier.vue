<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const filters = reactive({
  search: '',
  per_page: 10,
  page: 1
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0
})

const showForm = ref(false)
const editingId = ref(null)

const form = reactive({
  Supplier_Name: '',
  Location: '',
  Contact: ''
})

const resetForm = () => {
  editingId.value = null
  form.Supplier_Name = ''
  form.Location = ''
  form.Contact = ''
}

const isFocused = useWindowFocus()

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

const openCreate = () => {
  resetForm()
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  form.Supplier_Name = row.Supplier_Name || ''
  form.Location = row.Location || ''
  form.Contact = row.Contact || ''
  showForm.value = true
}

const fetchRows = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('user_token')
    const { data } = await axios.get('/api/suppliers/list', {
      params: {
        search: filters.search || undefined,
        per_page: filters.per_page,
        page
      },
      headers: { 
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      }
    })
    
    // Laravel pagination returns an object where the array is in data.data
    rows.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } catch (e) {
    console.error("Fetch Error:", e)
    error.value = 'Failed to load suppliers'
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  error.value = ''
  const token = localStorage.getItem('user_token')
  try {
    const config = { 
      headers: { 
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      } 
    }
    
    if (editingId.value) {
      await axios.put(`/api/suppliers/${editingId.value}`, form, config)
    } else {
      await axios.post('/api/suppliers', form, config)
    }
    
    showForm.value = false
    await fetchRows(pagination.current_page)
  } catch (e) {
    console.error("Save Error:", e)
    error.value = e?.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this supplier? This action cannot be undone if assets are linked.')) return
  
  const token = localStorage.getItem('user_token')
  try {
    await axios.delete(`/api/suppliers/${id}`, {
      headers: { 
        Authorization: `Bearer ${token}`,
        Accept: 'application/json'
      }
    })
    await fetchRows(pagination.current_page)
  } catch (e) {
    console.error("Delete Error:", e)
    alert(e.response?.data?.message || 'Delete failed')
  }
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Suppliers</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow flex items-center gap-2 text-sm font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add Supplier
      </button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
      <input 
        v-model="filters.search" 
        @keyup.enter="fetchRows(1)" 
        placeholder="Search name, location, or contact..." 
        class="border px-3 py-2 rounded text-sm w-64 focus:ring-2 focus:ring-blue-400 outline-none" 
      />
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
        <option :value="10">10 per page</option>
        <option :value="20">20 per page</option>
        <option :value="50">50 per page</option>
      </select>
      <button @click="fetchRows(1)" class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm transition-colors">Apply</button>
    </div>

    <p v-if="error" class="text-red-600 text-sm mb-2 font-medium">{{ error }}</p>

    <div v-if="showForm" class="bg-white p-5 rounded shadow-md border-t-4 border-[#3c8dbc] mb-4">
      <h2 class="text-xs font-bold uppercase text-gray-500 mb-4">{{ editingId ? 'Edit Supplier' : 'New Supplier' }}</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex flex-col">
          <label class="text-xs font-bold text-gray-600 mb-1">Supplier Name</label>
          <input v-model="form.Supplier_Name" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="e.g. Dell Technologies" />
        </div>
        <div class="flex flex-col">
          <label class="text-xs font-bold text-gray-600 mb-1">Location</label>
          <input v-model="form.Location" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="City, Country" />
        </div>
        <div class="flex flex-col">
          <label class="text-xs font-bold text-gray-600 mb-1">Contact Info</label>
          <input v-model="form.Contact" class="border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="Email or Phone" />
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button :disabled="saving" @click="submitForm" class="px-4 py-2 bg-blue-600 text-white rounded shadow text-sm font-bold hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ saving ? 'Saving...' : (editingId ? 'Update Supplier' : 'Save Supplier') }}
        </button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm font-bold hover:bg-gray-200 transition-colors">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#3c8dbc] rounded shadow-md overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
            <th class="p-4">Supplier Name</th>
            <th class="p-4">Location</th>
            <th class="p-4">Contact</th>
            <th class="p-4 text-center">Status</th>
            <th class="p-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px] divide-y">
          <tr v-if="loading"><td colspan="5" class="p-8 text-center text-gray-500 italic">Loading suppliers...</td></tr>
          <tr v-else-if="rows.length === 0"><td colspan="5" class="p-8 text-center text-gray-500 italic">No suppliers found.</td></tr>
          <tr v-for="supplier in rows" :key="supplier.id"
              :class="supplier.deleted_at ? 'bg-red-50' : 'hover:bg-gray-50'"
              class="transition-colors">
            <td class="p-4 font-bold text-[#3c8dbc]">{{ supplier.Supplier_Name }}</td>
            <td class="p-4 text-gray-600">{{ supplier.Location || 'N/A' }}</td>
            <td class="p-4 text-gray-600">{{ supplier.Contact || 'N/A' }}</td>
            <td class="p-4 text-center">
              <span v-if="supplier.deleted_at" class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">DELETED</span>
              <span v-else class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-bold">ACTIVE</span>
            </td>
            <td class="p-4">
              <div class="flex items-center justify-center gap-4">
                <button 
                  class="text-blue-500 hover:text-blue-700 disabled:opacity-30 transition-colors" 
                  @click="openEdit(supplier)"
                  :disabled="supplier.deleted_at"
                  title="Edit Supplier"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>

                <button 
                  class="text-red-500 hover:text-red-700 disabled:opacity-30 transition-colors" 
                  @click="removeRow(supplier.id)"
                  :disabled="supplier.deleted_at"
                  title="Delete Supplier"
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
        <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">Prev</button>
        <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="px-3 py-1 border rounded bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">Next</button>
      </div>
      <span>Page <b>{{ pagination.current_page }}</b> of {{ pagination.last_page }} <span class="mx-2 text-gray-300">|</span> {{ pagination.total }} records</span>
    </div>
  </div>
</template>