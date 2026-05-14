<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';
import { Search, ChevronLeft, ChevronRight, Plus, Filter, Edit3, Trash2, UserPlus, Info } from 'lucide-vue-next';


const rows = ref([])
const users = ref([])
const loading = ref(false)
const error = ref('')
const saving = ref(false)

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  return `KSH ${Number(amount).toLocaleString()}`;
}

const filters = reactive({ search: '', category: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', category: '', model_number: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })

const showAssignForm = ref(false)
const assignForm = reactive({ item: null, user_id: '', quantity: 1 })

const categoryOptions = computed(() => [...new Set((rows.value || []).filter(r => r).map(r => r.category).filter(Boolean))])

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
  Object.assign(form, { name: '', category: '', model_number: '', serial_no: '', total_qty: 0, remaining_qty: 0, price: '' })
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
    await axios.post(`/api/accessories/${assignForm.item.id}/assign`, { user_id: assignForm.user_id, quantity: assignForm.quantity })
    showAssignForm.value = false
    fetchRows(pagination.current_page)
  } catch (e) {
    alert('Failed to assign accessory: ' + (e.response?.data?.message || e.message))
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

onMounted(() => {
  fetchRows()
  loadUsers()
})
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Accessory <span class="text-vilcom-blue">Inventory</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Peripherals & Consumables Management
        </p>
      </div>
      
      <button @click="openCreate" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        New Accessory
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
            placeholder="Search peripherals..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.category" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
          <option value="">All Categories</option>
          <option v-for="opt in categoryOptions" :key="opt" :value="opt">{{ opt }}</option>
        </select>

        <button @click="fetchRows(1)" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Apply Filter</button>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Asset Identity</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Category</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Availability</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Unit Price</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Scanning Stock Level...
              </td>
            </tr>
            <tr v-for="item in rows" :key="item.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div>
                  <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">{{ item.name }}</div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono">
                    {{ item.model_number || 'STK' }} | {{ item.serial_no || item.id }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">{{ item.category }}</span>
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-col items-center">
                  <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
                    <div 
                      class="h-full transition-all duration-1000" 
                      :class="item.remaining_qty < 5 ? 'bg-vilcom-orange' : 'bg-vilcom-blue'"
                      :style="{ width: (item.remaining_qty / item.total_qty * 100) + '%' }"
                    ></div>
                  </div>
                  <span class="text-[10px] font-black" :class="item.remaining_qty < 1 ? 'text-red-600' : 'text-slate-600'">
                    {{ item.remaining_qty }} <span class="text-gray-300 mx-1">/</span> {{ item.total_qty }} <span class="ml-1 uppercase text-gray-400">In Stock</span>
                  </span>
                </div>
              </td>
              <td class="px-6 py-5 text-right font-black text-slate-700 text-sm">
                {{ formatMoney(item.price) }}
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openAssign(item)" :disabled="item.remaining_qty < 1" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-green-600 hover:border-green-600 hover:shadow-lg transition-all disabled:opacity-20" title="Assign to User">
                    <UserPlus class="size-4" />
                  </button>
                  <button @click="openEdit(item)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-vilcom-blue hover:border-vilcom-blue hover:shadow-lg transition-all" title="Edit Metadata">
                    <Edit3 class="size-4" />
                  </button>
                  <button @click="removeRow(item.id)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-red-500 hover:border-red-500 hover:shadow-lg transition-all" title="Delete Permanent">
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
          Quantum {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Total Items: {{ pagination.total }}
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

    <!-- Modals (Simplified Design) -->
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
              <h3 class="text-lg font-black text-slate-800 tracking-tight">Assign </h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ assignForm.item?.name }}</p>
            </div>
          </div>

          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Select User</label>
              <select v-model="assignForm.user_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-green-500/20 transition-all cursor-pointer">
                <option value="" disabled>Awaiting Selection...</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Quantity Transfer</label>
              <input v-model.number="assignForm.quantity" type="number" min="1" :max="assignForm.item?.remaining_qty" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-green-500/20 transition-all" />
              <div class="flex items-center gap-2 mt-2 ml-1">
                <Info class="size-3 text-blue-500" />
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Stock remaining: {{ assignForm.item?.remaining_qty }}</span>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <button @click="submitAssignment" :disabled="saving || !assignForm.user_id" class="w-full py-4 bg-green-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-green-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ saving ? 'Syncing...' : 'Authorize Transfer' }}
            </button>
            <button @click="showAssignForm = false" class="w-full py-4 text-gray-400 text-xs font-black uppercase tracking-widest hover:text-red-500 transition-colors">Cancel Allocation</button>
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
              <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ editingId ? 'Update Metadata' : 'New Stock Entry' }}</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Inventory Management Protocol</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Item Descriptor</label>
              <input v-model="form.name" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="e.g. Logitech MX Master 3S">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Sector Class</label>
              <select v-model="form.category" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Select Category...</option>
                <option v-for="opt in ['USB Drive','Headset','Printer','Webcam','Charger','Cables','Adapters','Motherboard','CPU','RAM','GPU','Hard Drive','Power Supply','Keyboard','Mouse','Network Card']" :key="opt" :value="opt">{{ opt }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Model Architecture</label>
              <input v-model="form.model_number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="M/N: 910-006557">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Identity/Serial</label>
              <input v-model="form.serial_no" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="S/N: 2228LZ03G688">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Total Stock</label>
              <input v-model.number="form.total_qty" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Available Stock</label>
              <input v-model.number="form.remaining_qty" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Valuation (KSh)</label>
              <input v-model.number="form.price" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="0.00">
            </div>
          </div>

          <div class="flex gap-4">
            <button @click="save" :disabled="saving" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ saving ? 'Synchronizing...' : (editingId ? 'Commit Changes' : 'Add') }}
            </button>
            <button @click="showForm = false" class="px-8 py-4 bg-gray-100 text-gray-400 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 hover:text-slate-600 transition-all">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>