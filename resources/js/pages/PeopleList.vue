<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const filters = reactive({ search: '', role: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', email: '', password: '', role: 'staff', is_verified: true })

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/users-list/paginated', {
      params: {
        search: filters.search || undefined,
        role: filters.role || undefined,
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
  Object.assign(form, { name: '', email: '', password: '', role: 'staff', is_verified: true })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, { name: row.name, email: row.email, password: '', role: row.role, is_verified: !!row.is_verified })
  showForm.value = true
}

const save = async () => {
  saving.value = true
  const payload = {
    name: form.name,
    email: form.email,
    role: form.role,
    is_verified: !!form.is_verified
  }
  if (form.password) payload.password = form.password

  try {
    if (editingId.value) await axios.put(`/api/users-list/${editingId.value}`, payload)
    else await axios.post('/api/users-list', { ...payload, password: form.password || 'password123' })
    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this user?')) return
  await axios.delete(`/api/users-list/${id}`)
  fetchRows(pagination.current_page)
}

onMounted(() => fetchRows())
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">People / Users</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] text-white px-4 py-2 rounded shadow hover:bg-[#367fa9]"><i class="fa fa-user-plus mr-2"></i> Add User</button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm" placeholder="Search name/email/role" />
      <select v-model="filters.role" class="border px-3 py-2 rounded text-sm">
        <option value="">All roles</option><option value="admin">admin</option><option value="staff">staff</option><option value="user">user</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select>
      <button @click="fetchRows(1)" class="px-3 py-2 bg-gray-800 text-white rounded text-sm">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-2">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Name" />
      <input v-model="form.email" class="border p-2 rounded" placeholder="Email" />
      <input v-model="form.password" type="password" class="border p-2 rounded" placeholder="Password" />
      <select v-model="form.role" class="border p-2 rounded"><option value="admin">admin</option><option value="staff">staff</option><option value="user">user</option></select>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.is_verified" type="checkbox" /> Active (Verified)</label>
      <div class="col-span-2 flex gap-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">{{ editingId ? 'Update' : 'Create' }}</button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#605ca8] rounded shadow-md overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[12px] uppercase text-gray-600 font-bold">
            <th class="p-4">Name</th>
            <th class="p-4">Email</th>
            <th class="p-4">Role</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <tr v-if="loading"><td colspan="5" class="p-4">Loading...</td></tr>
          <tr v-for="user in rows" :key="user.id" :class="user.is_verified ? 'hover:bg-gray-50 text-gray-700' : 'bg-red-50 text-red-700'" class="border-b transition-colors">
            <td class="p-4 font-bold">{{ user.name }}</td>
            <td class="p-4">{{ user.email }}</td>
            <td class="p-4 uppercase text-xs">{{ user.role }}</td>
            <td class="p-4"><span :class="user.is_verified ? 'bg-green-500' : 'bg-red-500'" class="px-2 py-1 rounded text-[10px] text-white font-bold">{{ user.is_verified ? 'ACTIVE' : 'INACTIVE' }}</span></td>
            <td class="p-4 text-right"><button class="mr-3 text-blue-500" @click="openEdit(user)"><i class="fa fa-edit"></i> Edit</button><button class="text-red-500" @click="removeRow(user.id)"><i class="fa fa-trash"></i> Delete</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>