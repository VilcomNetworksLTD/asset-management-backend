<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { Truck, Plus, Search, MapPin, Phone, CheckCircle2, XCircle, Edit3, Trash2, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';


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
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Logistics <span class="text-vilcom-blue">Suppliers</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          External Vendor & Acquisition Registry
        </p>
      </div>
      
      <button @click="openCreate" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        Onboard Supplier
      </button>
    </div>

    <!-- Creation/Edit Form -->
    <div v-if="showForm" class="bg-white rounded-[3rem] shadow-xl border border-gray-100 overflow-hidden animate-in fade-in slide-in-from-top-4 duration-500">
      <div class="p-10 space-y-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-vilcom-blue text-white rounded-2xl">
              <Truck class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ editingId ? 'Update Entity' : 'Register New Vendor' }}</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Entity Identification & Metadata</p>
            </div>
          </div>
          <button @click="showForm = false" class="text-gray-300 hover:text-red-500 transition-colors">
            <X class="size-6" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Legal Name</label>
            <input v-model="form.Supplier_Name" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="e.g. Oracle Systems" />
          </div>
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">HQ Location</label>
            <div class="relative group">
              <input v-model="form.Location" class="w-full bg-slate-50 border-none rounded-2xl p-5 pl-12 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="City, Building, Office..." />
              <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue" />
            </div>
          </div>
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Point of Contact</label>
            <div class="relative group">
              <input v-model="form.Contact" class="w-full bg-slate-50 border-none rounded-2xl p-5 pl-12 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="Email or Hotline..." />
              <Phone class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue" />
            </div>
          </div>
        </div>

        <div class="flex gap-4 pt-4">
          <button :disabled="saving" @click="submitForm" class="bg-slate-800 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-black/5 disabled:opacity-30">
            {{ saving ? 'Transmitting...' : (editingId ? 'Commit Changes' : 'Initialize Onboarding') }}
          </button>
          <button @click="showForm = false" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-slate-800 transition-colors">Cancel</button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30">
        <div class="relative group flex-1 max-w-md">
          <input 
            v-model="filters.search" 
            @keyup.enter="fetchRows(1)" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-full shadow-sm" 
            placeholder="Filter by name, location, or communication logs..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model.number="filters.per_page" @change="fetchRows(1)" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
          <option :value="10">10 Entries</option>
          <option :value="20">20 Entries</option>
          <option :value="50">50 Entries</option>
        </select>
        
        <button @click="fetchRows(1)" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Sync</button>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Supplier Identity</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Deployment Base</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Communication</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Operations</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Retreiving Logistics Partners...
              </td>
            </tr>
            <tr v-for="supplier in rows" :key="supplier.id" 
                :class="[supplier.status?.Status_Name === 'Deactivated' || supplier.deleted_at ? 'bg-red-50/50 grayscale' : 'group hover:bg-blue-50/30']"
                class="transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Truck class="size-5" />
                  </div>
                  <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                    {{ supplier.Supplier_Name }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                  <MapPin class="size-3 text-gray-300" />
                  {{ supplier.Location || 'GLOBAL' }}
                </div>
              </td>
              <td class="px-6 py-5 text-xs font-bold text-gray-500">
                <div class="flex items-center gap-2">
                  <Phone class="size-3 text-gray-300" />
                  {{ supplier.Contact || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-5 text-center">
                <span v-if="supplier.status?.Status_Name === 'Deactivated' || supplier.deleted_at" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-red-100 text-red-600 ring-1 ring-red-200">
                   Terminated
                </span>
                <span v-else class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-green-100 text-green-700 ring-1 ring-green-200">
                   Operational
                </span>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openEdit(supplier)" :disabled="supplier.deleted_at" class="p-2.5 bg-white border border-gray-100 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white hover:border-vilcom-blue transition-all shadow-sm" title="Modify Entity">
                    <Edit3 class="size-4" />
                  </button>
                  <button @click="removeRow(supplier.id)" :disabled="supplier.deleted_at" class="p-2.5 bg-white border border-gray-100 text-red-500 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all shadow-sm" title="Deactivate Vendor">
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && rows.length === 0">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                No active logistics entities detected.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="p-8 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
          Sector {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Active Partners: {{ pagination.total }}
        </div>
        <div class="flex items-center gap-3">
          <button @click="fetchRows(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronLeft class="size-4" />
          </button>
          <button @click="fetchRows(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronRight class="size-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>