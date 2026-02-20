<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const filters = reactive({ search: '', category: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', category: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })

const categories = computed(() => [...new Set(rows.value.map(r => r.category).filter(Boolean))])

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/components/list', {
      params: { search: filters.search || undefined, category: filters.category || undefined, per_page: filters.per_page, page }
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
  Object.assign(form, { name: '', category: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })
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
    if (editingId.value) await axios.put(`/api/components/${editingId.value}`, payload)
    else await axios.post('/api/components', payload)
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this component?')) return
  await axios.delete(`/api/components/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Components</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9] text-sm font-medium transition-colors">Create New</button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm" placeholder="Search" />
      <select v-model="filters.category" class="border px-3 py-2 rounded text-sm">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Component Name" />
      <input v-model="form.category" class="border p-2 rounded" placeholder="Category" />
      <input v-model="form.serial_no" class="border p-2 rounded" placeholder="Serial No" />
      <input v-model="form.total_qty" type="number" class="border p-2 rounded" placeholder="Total Qty" />
      <input v-model="form.remaining_qty" type="number" class="border p-2 rounded" placeholder="Remaining Qty" />
      <input v-model="form.price" type="number" class="border p-2 rounded" placeholder="Price" />
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-[3px] border-[#3c8dbc] rounded shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="text-[11px] uppercase text-gray-600 font-bold border-b bg-gray-50">
            <th class="p-3 border-r w-16 text-center">ID</th>
            <th class="p-3 border-r">Component Name</th>
            <th class="p-3 border-r">Category</th>
            <th class="p-3 border-r">Serial No.</th>
            <th class="p-3 border-r text-center">In Stock</th>
            <th class="p-3 border-r">Price</th>
            <th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px]">
          <tr v-if="loading"><td colspan="7" class="p-6 text-center text-gray-500">Loading components...</td></tr>
          <tr v-for="component in rows" :key="component.id" class="border-b hover:bg-gray-50 transition-colors">
            <td class="p-3 border-r text-center">{{ component.id }}</td>
            <td class="p-3 border-r font-medium text-[#3c8dbc]">{{ component.name }}</td>
            <td class="p-3 border-r">{{ component.category }}</td>
            <td class="p-3 border-r font-mono text-[11px]">{{ component.serial_no || 'N/A' }}</td>
            <td class="p-3 border-r text-center">{{ component.remaining_qty }} / {{ component.total_qty }}</td>
            <td class="p-3 border-r">${{ component.price }}</td>
            <td class="p-3 text-center space-x-2">
              <button class="text-blue-500" @click="openEdit(component)"><i class="fa fa-pencil-alt"></i> Edit</button>
              <button class="text-red-500" @click="removeRow(component.id)"><i class="fa fa-trash"></i> Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>