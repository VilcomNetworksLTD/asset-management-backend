<script setup>
import { onMounted, reactive, ref, watch, onUnmounted } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { 
  Users, UserPlus, Search, Filter, 
  Trash2, Edit3, ChevronRight, X,
  Laptop, Save, HardDrive, Keyboard,
  ShieldCheck, ShieldAlert
} from 'lucide-vue-next'

const isFocused = useWindowFocus()
const REFRESH_INTERVAL = 25000
let intervalId = null

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

const form = reactive({ name: '', email: '', password: '', role: 'staff', department_id: '' })
const departments = ref([])

const fetchDepartments = async () => {
    try {
        const { data } = await axios.get('/api/departments')
        departments.value = data
    } catch (error) {
        console.error("Failed to fetch departments", error)
    }
}

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

const viewUserDetails = async (user) => {
  loadingDetails.value = true
  showDetails.value = true
  selectedUserDetails.value = null 
  
  try {
    const { data } = await axios.get(`/api/users/${user.id}`)
    selectedUserDetails.value = data
  } catch (error) {
    console.error("Failed to load user history", error)
    showDetails.value = false
  } finally {
    loadingDetails.value = false
  }
}

const isCurrent = (item) => {
  if (!item || (item.pivot && item.pivot.returned_at)) return false;
  return true;
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, { name: '', email: '', password: '', role: 'staff', department_id: '' })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    email: row.email,
    password: '',
    role: row.role,
    department_id: row.department_id || ''
  })
  showForm.value = true
}

const save = async () => {
  saving.value = true
  const payload = { ...form }
  if (editingId.value) {
    delete payload.password 
    if (form.password) payload.password = form.password
  }

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
  if (!confirm('Execute deletion of this user profile?')) return
  await axios.delete(`/api/users-list/${id}`)
  fetchRows(pagination.current_page)
}

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

onMounted(() => {
    fetchRows()
    fetchDepartments()
    intervalId = setInterval(() => fetchRows(pagination.current_page), REFRESH_INTERVAL)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>

<template>
  <div class="space-y-8 font-inter">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Personnel <span class="text-vilcom-blue">Directory</span></h1>
        <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Manage system access, department alignment, and asset custodians.</p>
      </div>
      <button 
        @click="openCreate" 
        class="bg-vilcom-blue text-white px-8 py-4 rounded-[1.5rem] shadow-xl shadow-blue-900/20 flex items-center gap-3 text-xs font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all"
      >
        <UserPlus class="size-4" />
        New Operative
      </button>
    </div>

    <!-- Filters & Actions Bar -->
    <div class="bg-white p-2 rounded-[2rem] shadow-sm border border-gray-100 flex flex-wrap items-center gap-3">
      <div class="flex-1 min-w-[300px] relative group">
        <Search class="absolute left-6 top-1/2 -translate-y-1/2 size-4 text-gray-400 group-focus-within:text-vilcom-blue transition-colors" />
        <input 
          v-model="filters.search" 
          @keyup.enter="fetchRows(1)"
          class="w-full bg-gray-50/50 border-none rounded-[1.5rem] py-4 pl-14 pr-6 text-sm focus:ring-2 focus:ring-vilcom-blue/20 transition-all font-medium"
          placeholder="Search by name, email or designation..."
        >
      </div>
      
      <div class="flex items-center gap-3 px-4">
        <select v-model="filters.role" class="bg-white border-none rounded-xl py-3 pl-4 pr-10 text-xs font-black uppercase tracking-widest text-gray-500 focus:ring-2 focus:ring-blue-500/10 cursor-pointer">
          <option value="">All Architectures</option>
          <option value="admin">Administrator</option>
          <option value="staff">Standard Staff</option>
          <option value="hod">Department Head</option>
          <option value="management">Management</option>
        </select>
        <div class="h-6 w-px bg-gray-100"></div>
        <button @click="fetchRows(1)" class="p-3 bg-slate-900 text-white rounded-xl hover:bg-vilcom-blue transition-colors shadow-lg shadow-slate-900/20">
          <Filter class="size-4" />
        </button>
      </div>
    </div>

    <!-- Creation/Edit Form -->
    <transition name="fade-slide">
      <div v-if="showForm" class="bg-white p-10 rounded-[3rem] shadow-2xl border border-blue-50 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8">
           <button @click="showForm = false" class="p-2 text-gray-300 hover:text-red-500 transition-colors"><X class="size-6" /></button>
        </div>
        
        <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-3">
          <div class="size-1 bg-vilcom-blue rounded-full"></div>
          {{ editingId ? 'Update Credentials' : 'Configuration for New Operative' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Full Identity</label>
            <input v-model="form.name" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="John Doe">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Secure Email</label>
            <input v-model="form.email" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="j.doe@vilcom.co">
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Access Protocol</label>
            <select v-model="form.role" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20">
              <option value="admin">Administrator</option>
              <option value="staff">Standard Staff</option>
              <option value="hod">Department Head</option>
              <option value="management">Management</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Department Alignment</label>
            <select v-model="form.department_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20">
              <option value="">Awaiting Assignment</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
            </select>
          </div>
          <div class="space-y-2 lg:col-span-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Authentication Key</label>
            <input v-model="form.password" type="password" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" :placeholder="editingId ? '(Leave empty to retain)' : 'System Default Set to password123'">
          </div>
        </div>

        <div class="mt-12 flex gap-4">
          <button @click="save" :disabled="saving" class="px-10 py-4 bg-vilcom-blue text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-105 active:scale-95 transition-all">
            {{ saving ? 'Syncing...' : (editingId ? 'Push Updates' : 'Initialize Profile') }}
          </button>
          <button @click="showForm = false" class="px-10 py-4 bg-gray-100 text-gray-500 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
        </div>
      </div>
    </transition>

    <!-- Users Table -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-20 flex items-center justify-center">
         <div class="flex flex-col items-center gap-4">
           <div class="size-12 border-4 border-vilcom-blue/10 border-t-vilcom-blue rounded-full animate-spin"></div>
           <p class="text-[10px] font-black text-vilcom-blue uppercase tracking-widest">Scanning Directory...</p>
         </div>
      </div>

      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/50">
            <th class="p-8 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Full Identity</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Contact Node</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Access Level</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Department</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-center">Protocol Status</th>
            <th class="p-8 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="user in rows" :key="user.id" 
              class="group hover:bg-blue-50/30 transition-all duration-300">
            <td class="p-8">
              <button @click="viewUserDetails(user)" class="flex items-center gap-4 text-left group/btn">
                <div class="size-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-lg group-hover/btn:bg-vilcom-blue group-hover/btn:text-white transition-all">
                  {{ user.name.charAt(0) }}
                </div>
                <div>
                  <div class="font-black text-slate-800 text-sm group-hover/btn:text-vilcom-blue transition-colors">{{ user.name }}</div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">{{ user.department?.name || user.role || 'Staff Member' }}</div>
                </div>
              </button>
            </td>
            <td class="p-6">
              <div class="text-sm font-bold text-slate-600">{{ user.email }}</div>
            </td>
            <td class="p-6">
              <span class="px-3 py-1.5 rounded-lg text-[9px] font-black bg-slate-100 text-slate-500 uppercase tracking-widest">{{ user.role }}</span>
            </td>
            <td class="p-6">
              <div class="text-sm font-bold text-slate-600 italic">{{ user.department?.name || 'Unassigned' }}</div>
            </td>
            <td class="p-6 text-center">
              <div v-if="user.status?.Status_Name === 'Deactivated' || user.deleted_at" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest ring-1 ring-red-100">
                <ShieldAlert class="size-3" />
                Deactivated
              </div>
              <div v-else class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-600 rounded-xl text-[10px] font-black uppercase tracking-widest ring-1 ring-green-100">
                <ShieldCheck class="size-3" />
                Active
              </div>
            </td>
            <td class="p-8 text-right">
              <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="openEdit(user)" class="p-3 bg-blue-50 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white transition-all shadow-sm">
                  <Edit3 class="size-4" />
                </button>
                <button @click="removeRow(user.id)" class="p-3 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-sm">
                  <Trash2 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- User Details Slide-over -->
    <transition name="slide-panel">
      <div v-if="showDetails" class="fixed inset-0 z-[2000] flex justify-end">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDetails = false"></div>
        <div class="relative bg-[#f8fafc] w-full max-w-4xl h-full shadow-[0_0_100px_rgba(0,0,0,0.4)] overflow-y-auto flex flex-col">
          
          <!-- Slide Header -->
          <div class="bg-white p-10 border-b border-slate-100 flex justify-between items-center sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-6">
               <div class="size-16 rounded-[2rem] bg-vilcom-blue flex items-center justify-center text-white text-2xl font-black">
                 {{ selectedUserDetails?.name?.charAt(0) }}
               </div>
               <div>
                 <h2 class="text-2xl font-black text-slate-800 tracking-tighter">{{ selectedUserDetails?.name || 'Operative Profile' }}</h2>
                 <p class="text-[10px] font-black text-vilcom-orange uppercase tracking-[0.2em] mt-1">{{ selectedUserDetails?.role }} Access Protocol</p>
               </div>
            </div>
            <button @click="showDetails = false" class="p-4 bg-slate-50 text-slate-400 hover:text-red-500 rounded-2xl transition-all">
              <X class="size-6" />
            </button>
          </div>

          <div v-if="loadingDetails" class="flex-1 flex flex-col items-center justify-center py-20 bg-white/50">
             <div class="size-16 border-4 border-vilcom-blue/10 border-t-vilcom-blue rounded-full animate-spin mb-6"></div>
             <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Aggregating assigned assets...</p>
          </div>
          
          <div v-else-if="selectedUserDetails" class="p-10 space-y-12">
            
            <!-- Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                 <div class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Login Identity</div>
                 <div class="font-bold text-slate-700">{{ selectedUserDetails.email }}</div>
               </div>
               <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                 <div class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Sector Assignment</div>
                 <div class="font-bold text-slate-700">{{ selectedUserDetails.department?.name || 'Independent' }}</div>
               </div>
            </div>

            <!-- SECTION: HARDWARE -->
            <div class="space-y-6">
              <div class="flex items-center justify-between px-2">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                  <Laptop class="size-4 text-vilcom-blue" />
                  Hardware Assets
                </h3>
                <span class="px-4 py-1.5 bg-blue-50 text-vilcom-blue text-[10px] font-black rounded-full ring-1 ring-blue-100">{{ selectedUserDetails.assets?.length || 0 }} Items</span>
              </div>
              
              <div class="grid grid-cols-1 gap-4">
                <div v-for="asset in (selectedUserDetails.assets || []).filter(a => a)" :key="'ast-'+asset.id" 
                     class="bg-white p-6 rounded-[2.5rem] border border-slate-100 flex items-center justify-between group/card hover:shadow-xl transition-all">
                  <div class="flex items-center gap-6">
                    <div :class="['size-12 rounded-2xl flex items-center justify-center shadow-inner', isCurrent(asset) ? 'bg-green-50 text-green-600' : 'bg-slate-50 text-slate-300']">
                      <Laptop class="size-6" />
                    </div>
                    <div>
                      <div class="text-sm font-black text-slate-800 group-hover/card:text-vilcom-blue transition-colors">{{ asset.Asset_Name || asset.name }}</div>
                      <div class="text-[10px] font-bold text-slate-400 font-mono mt-1">{{ asset.Serial_No || asset.barcode }} | {{ asset.Asset_Category }}</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <span v-if="isCurrent(asset)" class="text-[9px] font-black px-4 py-2 bg-green-50 text-green-700 rounded-xl uppercase tracking-widest ring-1 ring-green-100">Activated</span>
                    <span v-else class="text-[9px] font-black px-4 py-2 bg-slate-100 text-slate-400 rounded-xl uppercase tracking-widest">Returned: {{ formatDate(asset.pivot?.returned_at) }}</span>
                  </div>
                </div>
                <div v-if="!selectedUserDetails.assets?.length" class="p-12 text-center bg-slate-50 border-2 border-dashed border-slate-200 rounded-[3rem] text-slate-400 italic font-bold">No hardware currently assigned.</div>
              </div>
            </div>

            <!-- SECTION: LICENSES -->
            <div class="space-y-6">
              <div class="flex items-center justify-between px-2">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                  <Save class="size-4 text-teal-600" />
                  Software Licenses
                </h3>
                <span class="px-4 py-1.5 bg-teal-50 text-teal-600 text-[10px] font-black rounded-full ring-1 ring-teal-100">{{ selectedUserDetails.licenses?.length || 0 }} Units</span>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="lic in selectedUserDetails.licenses" :key="'lic-'+lic.id" 
                     class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group/lic">
                  <div v-if="!isCurrent(lic)" class="absolute inset-0 bg-slate-50/50 backdrop-blur-[1px] z-10"></div>
                  <div class="flex flex-col h-full justify-between relative z-20">
                    <div class="flex items-start justify-between mb-4">
                      <div class="size-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <Save class="size-5" />
                      </div>
                      <span v-if="isCurrent(lic)" class="text-[8px] font-black px-2 py-1 bg-green-100 text-green-700 rounded-lg uppercase">Valid</span>
                    </div>
                    <div>
                      <div class="text-xs font-black text-slate-800 mb-1">{{ lic.name }}</div>
                      <div class="text-[9px] font-mono text-slate-400 truncate">{{ lic.product_key }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="!selectedUserDetails.licenses?.length" class="p-12 text-center bg-slate-50 border-2 border-dashed border-slate-200 rounded-[3rem] text-slate-400 italic font-bold">No license activations found.</div>
            </div>

            <!-- SECTION: COMPONENTS & ACCESSORIES INFRASTRUCTURE -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
               <!-- COMPONENTS -->
               <div class="space-y-6">
                  <h3 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                    <HardDrive class="size-4 text-indigo-600" />
                    Internal Parts
                  </h3>
                  <div class="space-y-3">
                    <div v-for="comp in selectedUserDetails.components" :key="'comp-'+comp.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                      <div class="flex items-center gap-4">
                        <div class="text-xs font-bold text-slate-700">{{ comp.name }}</div>
                      </div>
                      <div class="text-xs font-black text-indigo-600">x{{ comp.pivot?.quantity || 1 }}</div>
                    </div>
                  </div>
                  <div v-if="!selectedUserDetails.components?.length" class="text-center p-6 bg-slate-50 rounded-2xl text-xs text-slate-400 font-bold italic">No internal parts.</div>
               </div>

               <!-- ACCESSORIES -->
               <div class="space-y-6">
                  <h3 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                    <Keyboard class="size-4 text-vilcom-orange" />
                    Peripherals
                  </h3>
                  <div class="space-y-3">
                    <div v-for="acc in selectedUserDetails.accessories" :key="'acc-'+acc.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                      <div class="flex items-center gap-4">
                        <div class="text-xs font-bold text-slate-700">{{ acc.name }}</div>
                      </div>
                      <div class="text-xs font-black text-vilcom-orange">x{{ acc.pivot?.quantity || 1 }}</div>
                    </div>
                  </div>
                   <div v-if="!selectedUserDetails.accessories?.length" class="text-center p-6 bg-slate-50 rounded-2xl text-xs text-slate-400 font-bold italic">No peripherals.</div>
               </div>
            </div>

          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-slide-enter-from { opacity: 0; transform: translateY(-20px); }
.fade-slide-leave-to { opacity: 0; transform: translateY(20px); }

.slide-panel-enter-active, .slide-panel-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-panel-enter-from { transform: translateX(100%); }
.slide-panel-leave-to { transform: translateX(100%); }

/* Custom Scrollbar for slide-over */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>