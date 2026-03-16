<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const loadingDetails = ref(false)

const filters = reactive({ search: '', role: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const showDetails = ref(false)
const editingId = ref(null)
const selectedUserDetails = ref(null)

const form = reactive({ name: '', email: '', password: '', role: 'staff', is_verified: true, department: '' })

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

// --- Fetch User History ---
const viewUserDetails = async (user) => {
  loadingDetails.value = true
  showDetails.value = true
  selectedUserDetails.value = null 
  
  try {
    const { data } = await axios.get(`/api/users/${user.id}`)
    selectedUserDetails.value = data
  } catch (error) {
    console.error("Failed to load user history", error)
    alert("Could not load user details.")
    showDetails.value = false
  } finally {
    loadingDetails.value = false
  }
}

// --- LOGIC: Determine if an item is Current or Historical ---
const isCurrent = (item) => {
  if (item.pivot && item.pivot.returned_at) {
    return false; // It has a returned_at date, so it's history
  }
  return true; // Active
}

// Helper to format dates cleanly
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { name: '', email: '', password: '', role: 'staff', is_verified: true, department: '' })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    email: row.email,
    password: '',
    role: row.role,
    is_verified: !!row.is_verified,
    department: row.department || ''
  })
  showForm.value = true
}

const save = async () => {
  saving.value = true
  const payload = {
    name: form.name,
    email: form.email,
    role: form.role,
    department: form.department,
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
  <div class="p-6 relative">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">People / Users</h1>
      <button @click="openCreate" class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow flex items-center gap-2 text-sm font-medium transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="8.5" cy="7" r="4"></circle>
          <line x1="20" y1="8" x2="20" y2="14"></line>
          <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
        Add User
      </button>
    </div>

    <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
      <input v-model="filters.search" @keyup.enter="fetchRows(1)" class="border px-3 py-2 rounded text-sm focus:ring-2 focus:ring-blue-400 outline-none" placeholder="Search name/email/role" />
      <select v-model="filters.role" class="border px-3 py-2 rounded text-sm">
        <option value="">All roles</option>
        <option value="admin">admin</option>
        <option value="staff">staff</option>
        <option value="user">user</option>
      </select>
      <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
        <option :value="10">10 per page</option>
        <option :value="20">20 per page</option>
        <option :value="50">50 per page</option>
      </select>
      <button @click="fetchRows(1)" class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm transition-colors">Apply</button>
    </div>

    <div v-if="showForm" class="bg-white p-4 rounded shadow-sm mb-3 grid grid-cols-2 gap-3 border-t-4 border-[#3c8dbc]">
      <input v-model="form.name" class="border p-2 rounded" placeholder="Name" />
      <input v-model="form.email" class="border p-2 rounded" placeholder="Email" />
      <input v-model="form.password" type="password" class="border p-2 rounded" placeholder="Password" />
      <select v-model="form.role" class="border p-2 rounded">
        <option value="admin">admin</option>
        <option value="staff">staff</option>
        <option value="user">user</option>
      </select>
      <input v-model="form.department" class="border p-2 rounded" placeholder="Department" />
      <label class="flex items-center gap-2 text-sm"><input v-model="form.is_verified" type="checkbox" /> Active (Verified)</label>
      <div class="col-span-2 flex gap-2 pt-2">
        <button :disabled="saving" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 transition-colors">
          {{ editingId ? 'Update User' : 'Create User' }}
        </button>
        <button @click="showForm = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors">Cancel</button>
      </div>
    </div>

    <div class="bg-white border-t-4 border-[#605ca8] rounded shadow-md overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[11px] uppercase text-gray-600 font-bold">
            <th class="p-4">Name</th>
            <th class="p-4">Email</th>
            <th class="p-4">Role</th>
            <th class="p-4">Department</th>
            <th class="p-4 text-center">Status</th>
            <th class="p-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-[13px] divide-y">
          <tr v-if="loading"><td colspan="6" class="p-8 text-center text-gray-500 italic">Loading users...</td></tr>
          <tr v-for="user in rows" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="p-4">
              <button 
                @click="viewUserDetails(user)" 
                class="font-bold text-[#3c8dbc] hover:underline text-left cursor-pointer"
              >
                {{ user.name }}
              </button>
            </td>
            <td class="p-4 text-gray-600">{{ user.email }}</td>
            <td class="p-4 uppercase text-[10px] font-bold tracking-wider text-gray-400">{{ user.role }}</td>
            <td class="p-4 text-gray-600">{{ user.department || '-' }}</td>
            <td class="p-4 text-center">
              <span v-if="user.deleted_at" class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">DELETED</span>
              <span v-else :class="user.is_verified ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 rounded-full text-[10px] font-bold">
                {{ user.is_verified ? 'ACTIVE' : 'INACTIVE' }}
              </span>
            </td>
            <td class="p-4">
              <div class="flex items-center justify-center gap-4">
                <button class="text-blue-500 hover:text-blue-700 transition-colors" @click="openEdit(user)" title="Edit User">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button class="text-red-500 hover:text-red-700 transition-colors" @click="removeRow(user.id)" title="Delete User">
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

    <div v-if="showDetails" class="fixed inset-0 z-50 flex justify-end bg-black bg-opacity-40">
      <div class="bg-gray-50 w-full max-w-4xl h-full shadow-2xl overflow-y-auto animate-slide-in flex flex-col">
        
        <div class="bg-white p-6 border-b flex justify-between items-center sticky top-0 z-10 shadow-sm">
          <div>
            <h2 class="text-xl font-bold text-gray-800">User Inventory History</h2>
            <div v-if="selectedUserDetails" class="text-sm mt-1">
              <span class="font-bold text-[#3c8dbc]">{{ selectedUserDetails.name }}</span>
              <span class="text-gray-500"> | {{ selectedUserDetails.email }} | {{ selectedUserDetails.department }}</span>
            </div>
          </div>
          <button @click="showDetails = false" class="text-gray-500 hover:text-red-600 text-3xl transition-colors">&times;</button>
        </div>

        <div v-if="loadingDetails" class="flex-1 flex items-center justify-center text-gray-500 italic">
          Loading user history...
        </div>
        
        <div v-else-if="selectedUserDetails" class="p-6 space-y-6">
          
          <div class="bg-white p-4 rounded shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-700 border-b pb-2 mb-3">Hardware Assets <span class="bg-gray-100 px-2 rounded-full text-xs font-normal ml-2">{{ selectedUserDetails.assets?.length || 0 }}</span></h4>
            <div v-if="selectedUserDetails.assets?.length" class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-600 uppercase">
                    <th class="p-2 border font-semibold">Asset Name</th>
                    <th class="p-2 border font-semibold">Serial No.</th>
                    <th class="p-2 border font-semibold">Category</th>
                    <th class="p-2 border font-semibold text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="asset in selectedUserDetails.assets" :key="'ast-'+asset.id" 
                      :class="isCurrent(asset) ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 opacity-75'">
                    <td class="p-2 border font-medium">{{ asset.Asset_Name || asset.name || 'N/A' }}</td>
                    <td class="p-2 border">{{ asset.Serial_No || 'N/A' }}</td>
                    <td class="p-2 border">{{ asset.Asset_Category || 'N/A' }}</td>
                    <td class="p-2 border text-center">
                      <span v-if="isCurrent(asset)" class="px-2 py-1 rounded bg-green-100 text-green-700 font-bold text-[10px]">ACTIVE</span>
                      <span v-else class="px-2 py-1 rounded bg-gray-200 text-gray-500 font-bold text-[10px]">RETURNED</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No assets assigned.</p>
          </div>

          <div class="bg-white p-4 rounded shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-700 border-b pb-2 mb-3">Components <span class="bg-gray-100 px-2 rounded-full text-xs font-normal ml-2">{{ selectedUserDetails.components?.length || 0 }}</span></h4>
            <div v-if="selectedUserDetails.components?.length" class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-600 uppercase">
                    <th class="p-2 border font-semibold">Component Name</th>
                    <th class="p-2 border font-semibold">Serial No.</th>
                    <th class="p-2 border font-semibold">Category</th>
                    <th class="p-2 border font-semibold text-center">Qty</th>
                    <th class="p-2 border font-semibold text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="comp in selectedUserDetails.components" :key="'comp-'+comp.id" 
                      :class="isCurrent(comp) ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 opacity-75'">
                    <td class="p-2 border font-medium">{{ comp.name || 'N/A' }}</td>
                    <td class="p-2 border">{{ comp.serial_no || 'N/A' }}</td>
                    <td class="p-2 border">{{ comp.category || 'N/A' }}</td>
                    <td class="p-2 border text-center font-bold">{{ comp.pivot?.quantity || 1 }}</td>
                    <td class="p-2 border text-center">
                      <span v-if="isCurrent(comp)" class="px-2 py-1 rounded bg-green-100 text-green-700 font-bold text-[10px]">ACTIVE</span>
                      <div v-else>
                        <span class="px-2 py-1 rounded bg-gray-200 text-gray-500 font-bold text-[10px]">RETURNED</span>
                        <div class="text-[9px] mt-1">{{ formatDate(comp.pivot.returned_at) }}</div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No components assigned.</p>
          </div>

          <div class="bg-white p-4 rounded shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-700 border-b pb-2 mb-3">Accessories <span class="bg-gray-100 px-2 rounded-full text-xs font-normal ml-2">{{ selectedUserDetails.accessories?.length || 0 }}</span></h4>
            <div v-if="selectedUserDetails.accessories?.length" class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-600 uppercase">
                    <th class="p-2 border font-semibold">Accessory Name</th>
                    <th class="p-2 border font-semibold">Category</th>
                    <th class="p-2 border font-semibold">Model No.</th>
                    <th class="p-2 border font-semibold text-center">Qty</th>
                    <th class="p-2 border font-semibold text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="acc in selectedUserDetails.accessories" :key="'acc-'+acc.id" 
                      :class="isCurrent(acc) ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 opacity-75'">
                    <td class="p-2 border font-medium">{{ acc.name || 'N/A' }}</td>
                    <td class="p-2 border">{{ acc.category || 'N/A' }}</td>
                    <td class="p-2 border">{{ acc.model_number || 'N/A' }}</td>
                    <td class="p-2 border text-center font-bold">{{ acc.pivot?.quantity || 1 }}</td>
                    <td class="p-2 border text-center">
                      <span v-if="isCurrent(acc)" class="px-2 py-1 rounded bg-green-100 text-green-700 font-bold text-[10px]">ACTIVE</span>
                      <div v-else>
                        <span class="px-2 py-1 rounded bg-gray-200 text-gray-500 font-bold text-[10px]">RETURNED</span>
                        <div class="text-[9px] mt-1">{{ formatDate(acc.pivot.returned_at) }}</div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No accessories assigned.</p>
          </div>

          <div class="bg-white p-4 rounded shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-700 border-b pb-2 mb-3">Consumables <span class="bg-gray-100 px-2 rounded-full text-xs font-normal ml-2">{{ selectedUserDetails.consumables?.length || 0 }}</span></h4>
            <div v-if="selectedUserDetails.consumables?.length" class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-600 uppercase">
                    <th class="p-2 border font-semibold">Consumable Name</th>
                    <th class="p-2 border font-semibold">Category</th>
                    <th class="p-2 border font-semibold text-center">Qty Given</th>
                    <th class="p-2 border font-semibold text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="con in selectedUserDetails.consumables" :key="'con-'+con.id" 
                      :class="isCurrent(con) ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 opacity-75'">
                    <td class="p-2 border font-medium">{{ con.item_name || 'N/A' }}</td>
                    <td class="p-2 border">{{ con.category || 'N/A' }}</td>
                    <td class="p-2 border text-center font-bold">{{ con.pivot?.quantity || 1 }}</td>
                    <td class="p-2 border text-center">
                      <span v-if="isCurrent(con)" class="px-2 py-1 rounded bg-green-100 text-green-700 font-bold text-[10px]">ACTIVE</span>
                      <div v-else>
                        <span class="px-2 py-1 rounded bg-gray-200 text-gray-500 font-bold text-[10px]">RETURNED</span>
                        <div class="text-[9px] mt-1">{{ formatDate(con.pivot.returned_at) }}</div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No consumables assigned.</p>
          </div>

          <div class="bg-white p-4 rounded shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-700 border-b pb-2 mb-3">Licenses <span class="bg-gray-100 px-2 rounded-full text-xs font-normal ml-2">{{ selectedUserDetails.licenses?.length || 0 }}</span></h4>
            <div v-if="selectedUserDetails.licenses?.length" class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-600 uppercase">
                    <th class="p-2 border font-semibold">Software Name</th>
                    <th class="p-2 border font-semibold">Product Key</th>
                    <th class="p-2 border font-semibold">Expiration</th>
                    <th class="p-2 border font-semibold text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="lic in selectedUserDetails.licenses" :key="'lic-'+lic.id" 
                      :class="isCurrent(lic) ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 opacity-75'">
                    <td class="p-2 border font-medium">{{ lic.name || 'N/A' }}</td>
                    <td class="p-2 border text-xs font-mono bg-gray-50">{{ lic.product_key || 'N/A' }}</td>
                    <td class="p-2 border">{{ formatDate(lic.expiry_date) }}</td>
                    <td class="p-2 border text-center">
                      <span v-if="isCurrent(lic)" class="px-2 py-1 rounded bg-green-100 text-green-700 font-bold text-[10px]">ACTIVE</span>
                      <div v-else>
                        <span class="px-2 py-1 rounded bg-gray-200 text-gray-500 font-bold text-[10px]">RETURNED</span>
                        <div class="text-[9px] mt-1">{{ formatDate(lic.pivot.returned_at) }}</div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-400 italic">No licenses assigned.</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.3s ease-out forwards;
}
@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
</style>