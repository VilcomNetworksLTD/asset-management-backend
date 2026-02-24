<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const filters = reactive({ search: '', manufacturer: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', product_key: '', manufacturer: '', total_seats: 1, remaining_seats: 1, price: '' })

const manufacturerOptions = computed(() => [...new Set(rows.value.map(r => r.manufacturer).filter(Boolean))])

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/licenses/list', {
      params: { search: filters.search || undefined, manufacturer: filters.manufacturer || undefined, per_page: filters.per_page, page }
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
  Object.assign(form, { name: '', product_key: '', manufacturer: '', total_seats: 1, remaining_seats: 1, price: '' })
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
    total_seats: Number(form.total_seats),
    remaining_seats: Number(form.remaining_seats),
    price: form.price === '' ? null : Number(form.price)
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

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Licenses</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9] text-sm font-medium">New License</button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" placeholder="Search name/key/manufacturer" class="border px-3 py-2 rounded text-sm" />
      <select v-model="filters.manufacturer" class="border px-3 py-2 rounded text-sm">
        <option value="">All manufacturers</option>
        <option v-for="m in manufacturerOptions" :key="m" :value="m">{{ m }}</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Software Name" />
      <input v-model="form.product_key" class="border p-2 rounded" placeholder="Product Key" />
      <input v-model="form.manufacturer" class="border p-2 rounded" placeholder="Manufacturer" />
      <input v-model="form.total_seats" type="number" class="border p-2 rounded" placeholder="Total Seats" />
      <input v-model="form.remaining_seats" type="number" class="border p-2 rounded" placeholder="Remaining Seats" />
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
            <th class="p-3 border-r">ID</th><th class="p-3 border-r">Software Name</th><th class="p-3 border-r">Product Key</th><th class="p-3 border-r text-center">Seats</th><th class="p-3 border-r">Price</th><th class="p-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px]">
          <tr v-if="loading"><td colspan="6" class="p-3">Loading...</td></tr>
          <tr v-for="license in rows" :key="license.id" class="border-b hover:bg-gray-50 transition-colors">
            <td class="p-3 border-r">{{ license.id }}</td>
            <td class="p-3 border-r font-medium text-[#3c8dbc]">{{ license.name }}</td>
            <td class="p-3 border-r font-mono text-xs">{{ license.product_key || 'N/A' }}</td>
            <td class="p-3 border-r text-center">{{ license.remaining_seats }} / {{ license.total_seats }}</td>
            <td class="p-3 border-r">${{ license.price }}</td>
            <td class="p-3 text-center space-x-2"><button class="text-blue-500" @click="openEdit(license)"><i class="fa fa-pencil-alt"></i> Edit</button><button class="text-red-500" @click="removeRow(license.id)"><i class="fa fa-trash"></i> Delete</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>