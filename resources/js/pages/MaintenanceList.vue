<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import { useSettings } from '../composables/useSettings';
import { Send, X, Eye, Search, ChevronLeft, ChevronRight, Plus, Filter, Edit3, Trash2, UserPlus, Info, Save, Clock, AlertTriangle, Hammer, Archive, Ban, CheckCircle2 } from 'lucide-vue-next';


const rows = ref([])
const loading = ref(false)
const saving = ref(false)

const { settings } = useSettings();
function formatMoney(amount) {
  if (amount == null || amount === '') return '-';
  return `KSH ${Number(amount).toLocaleString()}`;
}

const filters = reactive({ search: '', status_id: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showForm = ref(false)
const editingId = ref(null)
const showEscalateModal = ref(false)
const activeMaintenance = ref(null)
const escalationForm = ref({
  item_name: '',
  estimated_cost: '',
  reason: ''
})
const escalating = ref(false)

const form = reactive({
  Asset_ID: '',
  Request_Date: '',
  Completion_Date: '',
  Maintenance_Type: '',
  Description: '',
  Cost: '',
})

const statuses = ref([])
const assets = ref([])

const isFocused = useWindowFocus()

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

/* ================= LOAD DATA ================= */

const loadAssets = async () => {
  const { data } = await axios.get('/api/assets')
  assets.value = data || []
}

const loadStatuses = async () => {
  const { data } = await axios.get('/api/statuses')
  statuses.value = data || []
}

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/maintenances/list', {
      params: {
        search: filters.search || undefined,
        status_id: filters.status_id || undefined,
        per_page: filters.per_page,
        page
      }
    })

    rows.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.total = data.total || 0
  } catch (error) {
    console.error('Failed to fetch maintenance logs', error)
  } finally {
    loading.value = false
  }
}

/* ================= FORM ACTIONS ================= */

const openCreate = () => {
  editingId.value = null
  Object.assign(form, {
    Asset_ID: '',
    Request_Date: '',
    Completion_Date: '',
    Maintenance_Type: '',
    Description: '',
    Cost: '',
  })
  showForm.value = true
}

const openEdit = (row) => {
  editingId.value = row.id
  Object.assign(form, {
    Asset_ID: row.Asset_ID,
    Request_Date: row.Request_Date?.slice(0, 16) || '',
    Completion_Date: row.Completion_Date?.slice(0, 16) || '',
    Maintenance_Type: row.Maintenance_Type || '',
    Description: row.Description || '',
    Cost: row.Cost || '',
  })
  showForm.value = true
}

const save = async () => {
  saving.value = true

  const payload = {
    ...form,
    Asset_ID: Number(form.Asset_ID),
    Cost: form.Cost === '' ? null : Number(form.Cost)
  }
  console.log("PAYLOAD BEING SENT:", payload)

  try {
    if (editingId.value) {
      await axios.put(`/api/maintenances/${editingId.value}`, payload)
    } else {
      await axios.post('/api/maintenances', payload)
    }

    showForm.value = false
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this maintenance record?')) return
  await axios.delete(`/api/maintenances/${id}`)
  fetchRows(pagination.current_page)
}

const archiveRow = async (id) => {
  if (!confirm('Are you sure you want to ARCHIVE/DISPOSE of this asset? This action will set both Maintenance and Asset status to Archived.')) return
  try {
    saving.value = true
    await axios.post(`/api/maintenances/${id}/archive`)
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const transitionStatus = async (row, status) => {
  try {
    saving.value = true
    await axios.put(`/api/maintenances/${row.id}`, {
      Workflow_Status: status
    })
    fetchRows(pagination.current_page)
  } finally {
    saving.value = false
  }
}

const openEscalateModal = (row) => {
  activeMaintenance.value = row
  escalationForm.value = {
    item_name: '',
    estimated_cost: '',
    reason: ''
  }
  showEscalateModal.value = true
}

const submitMaintenanceEscalation = async () => {
  if (!escalationForm.value.item_name || !escalationForm.value.reason) {
    alert('Please provide item name and reason for the purchase request.')
    return
  }
  
  escalating.value = true
  try {
    await axios.post('/api/purchase-requests/maintenance-escalate', {
      maintenance_id: activeMaintenance.value.id,
      item_name: escalationForm.value.item_name,
      estimated_cost: escalationForm.value.estimated_cost || null,
      reason: escalationForm.value.reason
    })
    showEscalateModal.value = false
    alert('Purchase request escalated to management for approval.')
    fetchRows(pagination.current_page)
  } catch (err) {
    console.error('Escalation failed:', err)
    alert('Failed to escalate purchase request.')
  } finally {
    escalating.value = false
  }
}



onMounted(async () => {
  await Promise.all([
    fetchRows(),
    loadStatuses(),
    loadAssets()
  ])
})
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Technical <span class="text-vilcom-blue">Maintenance</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Engineering & Asset Reliability Logs
        </p>
      </div>
      
      <button @click="openCreate" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        Schedule Intervention
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
            placeholder="Search tasks or assets..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.status_id" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[160px] shadow-sm text-slate-600">
          <option value="">All Flow Statuses</option>
          <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.Status_Name }}</option>
        </select>

        <button @click="fetchRows(1)" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Apply Filter</button>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Asset Identity</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Intervention Type</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Operational Timeline</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Resource Cost</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Lifecycle</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="6" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Accessing Engineering Logs...
              </td>
            </tr>
            <tr v-for="item in rows" :key="item.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Hammer class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ item.asset?.Asset_Name || 'Unknown Asset' }}
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono">
                      {{ item.asset?.Serial_No || 'N/A' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">
                   {{ item.Maintenance_Type }}
                </span>
              </td>
              <td class="px-6 py-5">
                 <div class="space-y-1">
                   <div class="flex items-center gap-2 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                     <Clock class="size-3 text-vilcom-blue" />
                     {{ item.Request_Date }}
                   </div>
                   <div class="text-[8px] font-bold text-gray-400 uppercase tracking-[0.2em] italic">
                     ETA: {{ item.Completion_Date || 'In Progress' }}
                   </div>
                 </div>
              </td>
              <td class="px-6 py-5 text-right font-black text-slate-700 text-sm">
                {{ formatMoney(item.Cost) }}
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-col items-center gap-1">
                  <span 
                    :class="[
                      'px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest ring-1 ring-white/50',
                      item.status?.Status_Name === 'Completed' ? 'bg-teal-50 text-teal-600 ring-teal-100' :
                      item.status?.Status_Name === 'Cancelled' ? 'bg-red-50 text-red-600 ring-red-100' :
                      item.status?.Status_Name === 'Archived' ? 'bg-gray-100 text-gray-400' :
                      'bg-blue-50 text-vilcom-blue ring-blue-100'
                    ]"
                  >
                    {{ item.status?.Status_Name || 'PENDING' }}
                  </span>
                  
                  <div v-if="item.status?.Status_Name !== 'Completed' && item.status?.Status_Name !== 'Cancelled' && item.status?.Status_Name !== 'Archived'" class="flex gap-2 mt-1">
                     <button @click="transitionStatus(item, 'In Progress')" v-if="['Scheduled', 'Out for Repair'].includes(item.status?.Status_Name)" class="text-[8px] font-black text-vilcom-blue uppercase hover:underline">Engage</button>
                     <button @click="transitionStatus(item, 'Completed')" v-if="item.status?.Status_Name === 'In Progress'" class="text-[8px] font-black text-teal-600 uppercase hover:underline">Finalize</button>
                  </div>
                </div>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openEscalateModal(item)" class="p-2.5 bg-white border border-gray-100 text-purple-500 rounded-xl hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all shadow-sm" title="Escalate Resources">
                    <Send class="size-4" />
                  </button>
                  <button @click="openEdit(item)" class="p-2.5 bg-white border border-gray-100 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white hover:border-vilcom-blue transition-all shadow-sm" title="Update Log">
                    <Edit3 class="size-4" />
                  </button>
                  <button v-if="item.status?.Status_Name !== 'Archived'" @click="archiveRow(item.id)" class="p-2.5 bg-white border border-gray-100 text-vilcom-orange rounded-xl hover:bg-vilcom-orange hover:text-white hover:border-vilcom-orange transition-all shadow-sm" title="Archive Asset">
                    <Archive class="size-4" />
                  </button>
                  <button @click="removeRow(item.id)" class="p-2.5 bg-white border border-gray-100 text-red-500 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all shadow-sm" title="Purge Record">
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
          Quantum {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Total Interventions: {{ pagination.total }}
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
    <!-- Escalation Modal -->
    <div v-if="showEscalateModal" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEscalateModal = false"></div>
      <div class="relative bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl">
              <Send class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">Resource Escalation</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Procurement Protocol for Parts</p>
            </div>
          </div>

          <div class="bg-purple-50/50 rounded-2xl p-6 border border-purple-100 space-y-1">
             <div class="text-[9px] font-black text-purple-400 uppercase tracking-widest">Linked Asset</div>
             <div class="text-sm font-black text-slate-800">{{ activeMaintenance?.asset?.Asset_Name }}</div>
          </div>

          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Required Component</label>
              <input v-model="escalationForm.item_name" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-purple-500/20 transition-all" placeholder="e.g. 512GB NVMe SSD">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Estimated Budget (KSH)</label>
              <input v-model="escalationForm.estimated_cost" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-purple-500/20 transition-all" placeholder="0.00">
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Technical Justification</label>
              <textarea v-model="escalationForm.reason" rows="3" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-purple-500/20 transition-all" placeholder="Why is this required for maintenance?"></textarea>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <button @click="submitMaintenanceEscalation" :disabled="escalating" class="w-full py-4 bg-purple-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-purple-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ escalating ? 'Transmitting...' : 'Submit to Management' }}
            </button>
            <button @click="showEscalateModal = false" class="w-full py-4 text-gray-400 text-xs font-black uppercase tracking-widest hover:text-red-500 transition-colors">Cancel Escalation</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showForm" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showForm = false"></div>
      <div class="relative bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-vilcom-blue text-white rounded-2xl shadow-lg shadow-blue-900/20">
              <Hammer class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">{{ editingId ? 'Update Log' : 'New Maintenance Protocol' }}</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Engineering Asset Management</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2 md:col-span-1">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Asset Selection</label>
              <select v-model="form.Asset_ID" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Select Asset Identity...</option>
                <option v-for="asset in assets" :key="asset.id" :value="asset.id">{{ asset.Asset_Name }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Intervention Category</label>
              <select v-model="form.Maintenance_Type" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="">Select Type...</option>
                <option value="Preventive">Preventive</option>
                <option value="Corrective">Corrective</option>
                <option value="Inspection">Inspection</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Resource Allocation (KSH)</label>
              <input v-model="form.Cost" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="0.00">
            </div>
            <div class="space-y-2">
               <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Protocol Initialization</label>
               <input v-model="form.Request_Date" type="datetime-local" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Technical Description</label>
              <textarea v-model="form.Description" rows="3" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="Detailed engineering report or task list..."></textarea>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button @click="save" :disabled="saving" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ saving ? 'Syncing...' : (editingId ? 'Execute Update' : 'Initialize Protocol') }}
            </button>
            <button @click="showForm = false" class="px-8 py-4 bg-gray-100 text-gray-400 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Abort</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>