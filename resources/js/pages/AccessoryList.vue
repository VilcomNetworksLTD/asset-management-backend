<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const error = ref('')
const saving = ref(false)

const filters = reactive({ search: '', category: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', category: '', model_number: '', total_qty: 0, remaining_qty: 0, price: '' })

const categoryOptions = computed(() => [...new Set(rows.value.map(r => r.category).filter(Boolean))])

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/accessories/list', {
      params: { search: filters.search || undefined, category: filters.category || undefined, per_page: filters.per_page, page }
    })
    rows.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } catch {
    error.value = 'Failed to load accessories'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { name: '', category: '', model_number: '', total_qty: 0, remaining_qty: 0, price: '' })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, row)
  showForm.value = true
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
    if (editingId.value) await axios.put(`/api/accessories/${editingId.value}`, payload)
    else await axios.post('/api/accessories', payload)
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this accessory?')) return
  await axios.delete(`/api/accessories/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Accessories</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9]">
        <i class="fa fa-plus mr-2"></i> New Accessory
      </button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm" placeholder="Search" />
      <select v-model="filters.category" class="border px-3 py-2 rounded text-sm">
        <option value="">All categories</option>
        <option v-for="c in categoryOptions" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Name" />
      <input v-model="form.category" class="border p-2 rounded" placeholder="Category" />
      <input v-model="form.model_number" class="border p-2 rounded" placeholder="Model Number" />
      <input v-model="form.total_qty" type="number" class="border p-2 rounded" placeholder="Total Qty" />
      <input v-model="form.remaining_qty" type="number" class="border p-2 rounded" placeholder="Remaining Qty" />
      <input v-model="form.price" type="number" class="border p-2 rounded" placeholder="Price" />
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#00c0ef] rounded shadow-md overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[12px] uppercase text-gray-600 font-bold">
            <th class="p-4">Name</th><th class="p-4">Category</th><th class="p-4">Model No.</th><th class="p-4 text-center">In Stock</th><th class="p-4">Price</th><th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <tr v-if="loading"><td colspan="6" class="p-4">Loading...</td></tr>
          <tr v-for="item in rows" :key="item.id" class="border-b hover:bg-gray-50 transition-colors">
            <td class="p-4 font-bold text-[#3c8dbc]">{{ item.name }}</td>
            <td class="p-4">{{ item.category }}</td>
            <td class="p-4 font-mono text-xs">{{ item.model_number || 'N/A' }}</td>
            <td class="p-4 text-center font-semibold">{{ item.remaining_qty }} / {{ item.total_qty }}</td>
            <td class="p-4">${{ item.price }}</td>
            <td class="p-4 text-right">
              <button class="text-blue-500 mr-3" @click="openEdit(item)"><i class="fa fa-edit"></i> Edit</button>
              <button class="text-red-500" @click="removeRow(item.id)"><i class="fa fa-trash"></i> Delete</button>
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