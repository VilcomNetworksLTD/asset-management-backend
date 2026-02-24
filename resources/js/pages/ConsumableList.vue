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
const form = reactive({ item_name: '', category: '', in_stock: 0, min_amt: 0, price: '' })

const categories = computed(() => [...new Set(rows.value.map(r => r.category).filter(Boolean))])

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/consumables/list', {
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
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { item_name: '', category: '', in_stock: 0, min_amt: 0, price: '' })
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
    in_stock: Number(form.in_stock),
    min_amt: Number(form.min_amt),
    price: form.price === '' ? null : Number(form.price)
  }
  try {
    if (editingId.value) await axios.put(`/api/consumables/${editingId.value}`, payload)
    else await axios.post('/api/consumables', payload)
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this consumable?')) return
  await axios.delete(`/api/consumables/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Consumables</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9]">
        <i class="fa fa-plus mr-2"></i> New Consumable
      </button>
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
      <input v-model="form.item_name" class="border p-2 rounded" placeholder="Item Name" />
      <input v-model="form.category" class="border p-2 rounded" placeholder="Category" />
      <input v-model="form.in_stock" type="number" class="border p-2 rounded" placeholder="In Stock" />
      <input v-model="form.min_amt" type="number" class="border p-2 rounded" placeholder="Minimum Amount" />
      <input v-model="form.price" type="number" class="border p-2 rounded" placeholder="Price" />
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#0073b7] rounded shadow-md">
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[12px] uppercase text-gray-600 font-bold">
            <th class="p-4">Item Name</th>
            <th class="p-4">Category</th>
            <th class="p-4">In Stock</th>
            <th class="p-4">Price</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <tr v-if="loading"><td colspan="5" class="p-4">Loading...</td></tr>
          <tr v-for="item in rows" :key="item.id" class="border-b hover:bg-gray-50">
            <td class="p-4 font-bold text-[#3c8dbc]">{{ item.item_name }}</td>
            <td class="p-4 text-gray-500">{{ item.category }}</td>
            <td class="p-4 font-semibold">{{ item.in_stock }}</td>
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