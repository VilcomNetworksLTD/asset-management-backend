<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const filters = reactive({
  search: '',
  category: '',
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
  Asset_Name: '',
  Asset_Category: '',
  Serial_No: '',
  Supplier_ID: '',
  Employee_ID: '',
  Status_ID: '1',
  Price: ''
})

const categoryOptions = computed(() => [...new Set(rows.value.map(r => r.Asset_Category).filter(Boolean))])

const resetForm = () => {
  editingId.value = null
  form.Asset_Name = ''
  form.Asset_Category = ''
  form.Serial_No = ''
  form.Supplier_ID = ''
  form.Employee_ID = ''
  form.Status_ID = '1'
  form.Price = ''
}

const openCreate = () => {
  resetForm()
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  form.Asset_Name = row.Asset_Name || ''
  form.Asset_Category = row.Asset_Category || ''
  form.Serial_No = row.Serial_No || ''
  form.Supplier_ID = row.Supplier_ID || ''
  form.Employee_ID = row.Employee_ID || ''
  form.Status_ID = row.Status_ID || '1'
  form.Price = row.Price || ''
  showForm.value = true
}

const fetchRows = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get('/api/assets/list', {
      params: {
        search: filters.search || undefined,
        category: filters.category || undefined,
        per_page: filters.per_page,
        page
      }
    })
    rows.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } catch (e) {
    error.value = 'Failed to load assets'
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      ...form,
      Supplier_ID: Number(form.Supplier_ID),
      Employee_ID: Number(form.Employee_ID),
      Status_ID: Number(form.Status_ID),
      Price: form.Price === '' ? null : Number(form.Price)
    }

    if (editingId.value) {
      await axios.put(`/api/assets/${editingId.value}`, payload)
    } else {
      await axios.post('/api/assets', payload)
    }

    showForm.value = false
    await fetchRows(pagination.current_page)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this asset?')) return
  await axios.delete(`/api/assets/${id}`)
  await fetchRows(pagination.current_page)
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-semibold text-gray-800">Assets</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9] text-sm font-medium">Create New</button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" placeholder="Search name/category/serial" class="border px-3 py-2 rounded text-sm" />
      <select v-model="filters.category" class="border px-3 py-2 rounded text-sm">
        <option value="">All categories</option>
        <option v-for="c in categoryOptions" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
        <option :value="10">10</option>
        <option :value="20">20</option>
        <option :value="50">50</option>
      </select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <p v-if="error" class="text-red-600 text-sm mb-2">{{ error }}</p>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.Asset_Name" class="border p-2 rounded" placeholder="Asset Name" />
      <input v-model="form.Asset_Category" class="border p-2 rounded" placeholder="Category" />
      <input v-model="form.Serial_No" class="border p-2 rounded" placeholder="Serial No" />
      <input v-model="form.Price" type="number" class="border p-2 rounded" placeholder="Price" />
      <input v-model="form.Supplier_ID" type="number" class="border p-2 rounded" placeholder="Supplier ID" />
      <input v-model="form.Employee_ID" type="number" class="border p-2 rounded" placeholder="Employee ID" />
      <input v-model="form.Status_ID" type="number" class="border p-2 rounded" placeholder="Status ID" />
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="submitForm" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-[3px] border-[#3c8dbc] rounded shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="text-[11px] uppercase text-gray-600 font-bold border-b">
            <th class="p-3 border-r">ID</th>
            <th class="p-3 border-r">Name</th>
            <th class="p-3 border-r">Category</th>
            <th class="p-3 border-r">Serial</th>
            <th class="p-3 border-r">Price</th>
            <th class="p-3 border-r">Status</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px]">
          <tr v-if="loading"><td colspan="7" class="p-3">Loading...</td></tr>
          <tr v-for="asset in rows" :key="asset.id" class="border-b hover:bg-gray-50">
            <td class="p-3 border-r">{{ asset.id }}</td>
            <td class="p-3 border-r">{{ asset.Asset_Name }}</td>
            <td class="p-3 border-r">{{ asset.Asset_Category }}</td>
            <td class="p-3 border-r">{{ asset.Serial_No }}</td>
            <td class="p-3 border-r">{{ asset.Price }}</td>
            <td class="p-3 border-r">{{ asset.status?.Status_Name || 'N/A' }}</td>
            <td class="p-3 text-center space-x-2">
              <button class="text-blue-500 hover:text-blue-700" @click="openEdit(asset)">
    <i class="fa fa-pencil"></i> Edit
  </button>
  <button class="text-red-500 hover:text-red-700" @click="removeRow(asset.id)">
    <i class="fa fa-trash"></i> Delete
  </button>
            </td>
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