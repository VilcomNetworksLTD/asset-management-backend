<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';
import { Search, ChevronLeft, ChevronRight, Plus, Filter, Edit3, Trash2, UserPlus, Info, Save, Clock } from 'lucide-vue-next';


const rows = ref([])
const users = ref([])
const departments = ref([]) // For the department dropdown
const loading = ref(false)
const saving = ref(false)

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  return `KSH ${Number(amount).toLocaleString()}`;
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
  } catch (error) {
    console.error('Failed to fetch licenses', error)
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
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Software <span class="text-vilcom-blue">Licenses</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Digital Asset & Compliance Management
        </p>
      </div>
      
      <button @click="openCreate" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        New License
      </button>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30">
        <div class="relative group">
          <input 
            v-model="filters.search" 
            @keyup.enter="fetchRows(1)" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-64 shadow-sm" 
            placeholder="Search license or key..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.manufacturer" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
          <option value="">All Manufacturers</option>
          <option v-for="m in manufacturerOptions" :key="m" :value="m">{{ m }}</option>
        </select>

        <button @click="fetchRows(1)" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Apply Filter</button>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Software Detail</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Seat Capacity</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Lifecycle</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Investment</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Aggregating License Keys...
              </td>
            </tr>
            <tr v-for="license in rows" :key="license.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Save class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">{{ license.name }}</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono max-w-[200px] truncate">
                      {{ license.product_key || 'VOL-LICENSE' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-col items-center">
                  <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
                    <div 
                      class="h-full transition-all duration-1000" 
                      :class="license.remaining_seats < 2 ? 'bg-vilcom-orange' : 'bg-teal-500'"
                      :style="{ width: (license.remaining_seats / license.total_seats * 100) + '%' }"
                    ></div>
                  </div>
                  <span class="text-[10px] font-black" :class="license.remaining_seats < 1 ? 'text-red-600' : 'text-slate-600'">
                    {{ license.remaining_seats }} <span class="text-gray-300 mx-1">/</span> {{ license.total_seats }} <span class="ml-1 uppercase text-gray-400">Seats</span>
                  </span>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest text-slate-500">
                    <Clock class="size-3 text-vilcom-orange" />
                    {{ license.expiry_date || 'Perpetual' }}
                  </div>
                  <div class="text-[8px] font-bold text-gray-400 uppercase tracking-[0.2em]">
                    {{ license.allocation_type }} | {{ license.renewal_type }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 text-right font-black text-slate-700 text-sm">
                {{ formatMoney(license.price) }}
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openAssign(license)" :disabled="license.remaining_seats < 1" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-green-600 hover:border-green-600 hover:shadow-lg transition-all disabled:opacity-20" title="Assign User">
                    <UserPlus class="size-4" />
                  </button>
                  <button @click="openEdit(license)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-vilcom-blue hover:border-vilcom-blue hover:shadow-lg transition-all" title="Edit Metadata">
                    <Edit3 class="size-4" />
                  </button>
                  <button @click="removeRow(license.id)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-red-500 hover:border-red-500 hover:shadow-lg transition-all" title="Revoke Permanent">
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="p-8 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
          Quantum {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Total Nodes: {{ pagination.total }}
        </div>
        <div class="flex items-center gap-3">
          <button :disabled="pagination.current_page <= 1" @click="fetchRows(pagination.current_page - 1)" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronLeft class="size-4" />
          </button>
          <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchRows(pagination.current_page + 1)" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronRight class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- Assign Modal -->
    <div v-if="showAssignForm" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAssignForm = false"></div>
      <div class="relative bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-2xl">
              <UserPlus class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">Seat Allocation</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ assignForm.item?.name }}</p>
            </div>
          </div>

          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Recipient Operative</label>
              <select v-model="assignForm.user_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-green-500/20 transition-all cursor-pointer">
                <option value="" disabled>Select User Identity...</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
              <div class="flex items-center gap-2 mt-2 ml-1">
                <Info class="size-3 text-blue-500" />
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Available Seats: {{ assignForm.item?.remaining_seats }}</span>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <button @click="submitAssignment" :disabled="saving || !assignForm.user_id" class="w-full py-4 bg-green-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-green-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ saving ? 'Syncing...' : 'Confirm Allocation' }}
            </button>
            <button @click="showAssignForm = false" class="w-full py-4 text-gray-400 text-xs font-black uppercase tracking-widest hover:text-red-500 transition-colors">Cancel Assignment</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Form -->
    <div v-if="showForm" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showForm = false"></div>
      <div class="relative bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-vilcom-blue text-white rounded-2xl shadow-lg shadow-blue-900/20">
              <Edit3 class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ editingId ? 'Update Identity' : 'New License Entry' }}</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Digital Asset Protocol</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2 md:col-span-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Software Descriptor</label>
              <input v-model="form.name" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="e.g. Adobe Creative Cloud">
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Product Signature (Key)</label>
              <input v-model="form.product_key" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="XXXXX-XXXXX-XXXXX-XXXXX">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Total Capacity</label>
              <input v-model.number="form.total_seats" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Remaining Units</label>
              <input v-model.number="form.remaining_seats" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Lifecycle Expiry</label>
              <input v-model="form.expiry_date" type="date" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Sector Alignment</label>
              <select v-model="form.department_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Universal Access</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Allocation Logic</label>
              <select v-model="form.allocation_type" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Select Type...</option>
                <option value="Per User">Per User</option>
                <option value="Per Device">Per Device</option>
                <option value="Per Processor">Per Processor</option>
                <option value="Site License">Site License</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Renewal Protocol</label>
              <select v-model="form.renewal_type" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Select Protocol...</option>
                <option value="Annual">Annual</option>
                <option value="Perpetual">Perpetual</option>
                <option value="Monthly">Monthly</option>
                <option value="Trial">Trial</option>
              </select>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button @click="save" :disabled="saving" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ saving ? 'Synchronizing...' : (editingId ? 'Push Updates' : 'Initialize Record') }}
            </button>
            <button @click="showForm = false" class="px-8 py-4 bg-gray-100 text-gray-400 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>