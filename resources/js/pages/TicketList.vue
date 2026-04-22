<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Support <span class="text-vilcom-blue">Tickets</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Centralized Response & Resolution Hub
        </p>
      </div>
      
      <div class="bg-white p-1 rounded-2xl shadow-sm border border-gray-100 flex gap-1">
        <button @click="currentTab = 'asset'" :class="tabClass('asset')">Inventory Requests</button>
        <button @click="currentTab = 'general'" :class="tabClass('general')">IT Support</button>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30">
        <div class="relative group">
          <input 
            v-model="filters.search" 
            @keyup.enter="fetchRows(1)" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-64 shadow-sm" 
            placeholder="Search tickets..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.priority" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
          <option value="">All Priorities</option>
          <option value="low">Low Priority</option>
          <option value="medium">Medium Priority</option>
          <option value="high">High Priority</option>
        </select>

        <button @click="fetchRows(1)" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Apply Filter</button>
      </div>

      <!-- Update Panel -->
      <div v-if="showUpdate" class="p-10 bg-blue-50/30 border-b border-blue-100 space-y-6">
        <div class="flex items-center gap-3 mb-2">
          <div class="p-2 bg-vilcom-blue rounded-lg text-white">
            <Edit3 class="size-4" />
          </div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Synchronize Ticket Status</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Description refinement</label>
              <textarea v-model="updateForm.description" rows="3" class="w-full bg-white border-none rounded-2xl p-6 text-sm font-bold shadow-sm focus:ring-2 focus:ring-vilcom-blue"></textarea>
           </div>
           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Escalation Level</label>
              <select v-model="updateForm.priority" class="w-full bg-white border-none rounded-2xl p-6 text-sm font-bold shadow-sm focus:ring-2 focus:ring-vilcom-blue appearance-none">
                <option value="low">Routine (Low)</option>
                <option value="medium">Critical (Medium)</option>
                <option value="high">Emergency (High)</option>
              </select>
           </div>
           <div class="col-span-2 space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Internal Communication Log</label>
              <textarea v-model="updateForm.communication" class="w-full bg-white border-none rounded-2xl p-6 text-sm font-bold shadow-sm focus:ring-2 focus:ring-vilcom-blue" placeholder="Add resolution details or handover notes..."></textarea>
           </div>
        </div>

        <div class="flex gap-4">
           <button @click="saveUpdate" :disabled="saving" class="bg-vilcom-blue text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-900/10 hover:opacity-90 transition-all active:scale-95">
             {{ saving ? 'SYNCING...' : 'COMMIT CHANGES' }}
           </button>
           <button @click="showUpdate = false" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-red-500 transition-colors">Abort Update</button>
        </div>
      </div>

      <!-- Assign Panel -->
      <div v-if="showAssign" class="p-10 bg-green-50/20 border-b border-green-100 space-y-8">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-3">
              <div class="p-2 bg-green-600 rounded-lg text-white">
                <UserPlus class="size-4" />
              </div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Inventory Assignment Workflow</h3>
           </div>
           <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest bg-green-50 px-3 py-1 rounded-lg">Fulfilling Ticket #{{ assignTicket?.id }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Asset Allocation</label>
              <div class="relative">
                <input 
                  v-model="assignSearch" 
                  class="w-full bg-white border-none rounded-2xl p-5 pl-12 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue" 
                  placeholder="Scan or Search Assets..." 
                />
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-gray-300" />
              </div>
              <select v-model="assignForm.asset_id" class="w-full bg-white border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none">
                <option value="">Select Target Hardware...</option>
                <option v-for="asset in assignOptions" :key="asset.id" :value="asset.id">
                  {{ asset.Asset_Name }} [{{ asset.barcode || id }}]
                </option>
              </select>
           </div>

           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Peripheral Bundling</label>
              <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100 space-y-3 max-h-48 overflow-y-auto custom-scrollbar">
                <div v-for="(row, idx) in assignForm.accessory_allocations" :key="idx" class="flex gap-2">
                   <select v-model="row.id" class="flex-1 bg-gray-50 border-none rounded-xl p-3 text-xs font-bold focus:ring-1 focus:ring-vilcom-blue">
                      <option value="" disabled>Select peripheral</option>
                      <option v-for="item in accessoryOptions" :key="item.id" :value="item.id">{{ item.name }} ({{ item.remaining_qty }})</option>
                   </select>
                   <input v-model.number="row.qty" type="number" class="w-16 bg-gray-50 border-none rounded-xl p-3 text-xs font-bold text-center" />
                   <button @click="removeAccessoryRow(idx)" class="size-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors">×</button>
                </div>
                <button @click="addAccessoryRow" class="w-full py-3 border-2 border-dashed border-gray-100 rounded-xl text-[10px] font-black text-gray-300 uppercase tracking-widest hover:border-vilcom-blue hover:text-vilcom-blue transition-all">+ Add Peripheral</button>
              </div>
           </div>
        </div>

        <div class="flex gap-4">
           <button @click="submitAssign" class="bg-green-600 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-green-900/10 hover:opacity-90 transition-all active:scale-95">AUTHORIZE ASSIGNMENT</button>
           <button @click="showAssign = false" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-red-500 transition-colors">Cancel Allocation</button>
        </div>
      </div>

      <!-- Escalate Panel -->
      <div v-if="showEscalate" class="p-10 bg-purple-50/20 border-b border-purple-100 space-y-8">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-3">
              <div class="p-2 bg-purple-600 rounded-lg text-white">
                <ArrowUpRight class="size-4" />
              </div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Escalate to Management</h3>
           </div>
           <p class="text-[10px] font-bold text-purple-600 uppercase tracking-widest bg-purple-50 px-3 py-1 rounded-lg">Ticket #{{ escalateTicket?.id }}</p>
        </div>

        <div class="space-y-6">
           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Item Name *</label>
              <input 
                v-model="escalateForm.item_name" 
                class="w-full bg-white border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue" 
                placeholder="Enter item name for purchase..." 
              />
           </div>

           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estimated Cost</label>
              <input 
                v-model="escalateForm.estimated_cost" 
                type="number"
                class="w-full bg-white border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue" 
                placeholder="Enter estimated cost..." 
              />
           </div>

           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Reason *</label>
              <textarea 
                v-model="escalateForm.reason" 
                rows="4"
                class="w-full bg-white border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue" 
                placeholder="Explain why this needs to be purchased..."
              ></textarea>
           </div>
        </div>

        <div class="flex gap-4">
           <button @click="submitEscalation" class="bg-purple-600 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-purple-900/10 hover:opacity-90 transition-all active:scale-95">ESCALATE TO MANAGEMENT</button>
           <button @click="showEscalate = false" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-red-500 transition-colors">Cancel</button>
        </div>
      </div>

      <!-- Reject Panel -->
      <div v-if="showReject" class="p-10 bg-red-50/20 border-b border-red-100 space-y-8">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-3">
              <div class="p-2 bg-red-600 rounded-lg text-white">
                <AlertCircle class="size-4" />
              </div>
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Decline Asset Request</h3>
           </div>
           <p class="text-[10px] font-bold text-red-600 uppercase tracking-widest bg-red-50 px-3 py-1 rounded-lg">Ticket #{{ rejectTicket?.id }}</p>
        </div>

        <div class="space-y-6">
           <div class="space-y-4">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Reason for Rejection *</label>
              <textarea 
                v-model="rejectForm.reason" 
                rows="4"
                class="w-full bg-white border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue" 
                placeholder="Explain why this request is being declined..."
              ></textarea>
           </div>
        </div>

        <div class="flex gap-4">
           <button @click="submitReject" class="bg-red-600 text-white px-10 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-red-900/10 hover:opacity-90 transition-all active:scale-95">CONFIRM REJECTION</button>
           <button @click="showReject = false" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-red-500 transition-colors">Cancel</button>
        </div>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto border border-slate-200 shadow-xl rounded-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b-2 border-slate-200">
              <th v-if="role === 'admin'" class="p-4 text-[11px] font-black text-slate-700 uppercase tracking-[0.2em] w-48 border-r border-slate-200">Staff Member</th>
              <th class="p-4 text-[11px] font-black text-slate-700 uppercase tracking-[0.2em] w-auto border-r border-slate-200">Equipment & Requirement Details</th>
              <th class="p-4 text-[11px] font-black text-slate-700 uppercase tracking-[0.2em] w-24 border-r border-slate-200 text-center">Priority</th>
              <th class="p-4 text-[11px] font-black text-slate-700 uppercase tracking-[0.2em] w-36 text-center border-r border-slate-200">Protocol Status</th>
              <th class="p-4 text-[11px] font-black text-slate-700 uppercase tracking-[0.2em] w-48 text-right">Interactions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td :colspan="role === 'admin' ? 5 : 4" class="p-20 text-center">
                 <Loader />
              </td>
            </tr>
            <tr v-for="ticket in rows" :key="ticket.id" class="group transition-all hover:bg-slate-50 border-b border-slate-200">
              <td v-if="role === 'admin'" class="p-4 border-r border-slate-200 bg-white group-hover:bg-slate-50 transition-colors">
                 <div class="flex items-center gap-3">
                    <div class="size-10 bg-slate-100 rounded-full flex items-center justify-center font-black text-slate-400 text-xs uppercase shadow-inner border border-slate-200">
                       {{ ticket.user?.name.charAt(0) }}
                    </div>
                    <div>
                      <div class="font-black text-slate-800 text-sm tracking-tight">{{ ticket.user?.name }}</div>
                      <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">{{ ticket.user?.department?.name || '' }}</div>
                    </div>
                 </div>
              </td>

              <td class="p-4 border-r border-slate-200 bg-white group-hover:bg-slate-50 transition-colors">
                 <div class="space-y-1.5 max-w-xl">
                    <div class="font-black text-slate-900 text-base tracking-tight group-hover:text-vilcom-blue transition-colors">
                       {{ currentTab === 'asset' ? (ticket.issue?.asset?.Asset_Name || extractRequestedCategory(ticket) || 'Inventory Request') : extractSubject(ticket.Description) }}
                    </div>
                    <div class="text-[11px] font-bold text-slate-600 leading-relaxed italic whitespace-pre-wrap">
                       {{ cleanDescription(ticket.Description) }}
                    </div>
                    
                    <!-- Rejection Reason Display -->
                    <div v-if="isRejected(ticket)" class="mt-4 p-4 bg-red-50 border-2 border-red-200 rounded-2xl flex gap-3 items-start animate-in fade-in slide-in-from-top-1">
                       <AlertCircle class="size-4 text-red-600 shrink-0 mt-0.5" />
                       <div>
                          <p class="text-[10px] font-black text-red-700 uppercase tracking-widest">Rejection Reason</p>
                          <p class="text-xs font-bold text-red-900 mt-1 leading-relaxed">{{ extractRejectionReason(ticket.Communication_log) }}</p>
                       </div>
                    </div>
                 </div>
              </td>

              <td class="p-4 border-r border-slate-200 bg-white group-hover:bg-slate-50 transition-colors text-center">
                <span :class="priorityClass(ticket.Priority)" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200">
                  {{ ticket.Priority }}
                </span>
              </td>

              <td class="p-4 text-center border-r border-slate-200 bg-white group-hover:bg-slate-50 transition-colors">
                <div :class="statusContainerClass(ticket.status?.Status_Name)" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-slate-200 transition-all">
                   <div :class="statusDotClass(ticket.status?.Status_Name)" class="size-1.5 rounded-full animate-pulse"></div>
                   <span class="text-[10px] font-black uppercase tracking-widest">{{ ticket.status?.Status_Name || 'Processing' }}</span>
                </div>
              </td>

              <td class="p-4 text-right bg-white group-hover:bg-slate-50 transition-colors">
                <div class="flex justify-end gap-3 opacity-40 group-hover:opacity-100 transition-opacity">
                   <!-- ACTION TRANSITIONS -->
                   <template v-if="!isRejected(ticket)">
                      <button @click="openUpdate(ticket)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-vilcom-blue hover:border-vilcom-blue hover:shadow-lg transition-all" title="Edit Ticket & Add Note">
                        <Edit3 class="size-4" />
                      </button>

                      <button v-if="role === 'admin' && currentTab === 'general' && !isResolved(ticket)" @click="resolveTicket(ticket)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-green-600 hover:border-green-600 hover:shadow-lg transition-all" title="Mark as Resolved">
                        <CheckCircle class="size-4" />
                      </button>

                      <button v-if="role === 'admin' && currentTab === 'asset' && !ticket.issue?.asset && !isResolved(ticket)" @click="openAssign(ticket)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-green-600 hover:border-green-600 hover:shadow-lg transition-all" title="Assign Asset to User">
                        <Package class="size-4" />
                      </button>

                      <button v-if="role === 'admin' && currentTab === 'asset' && !ticket.issue?.asset && !isResolved(ticket)" @click="openReject(ticket)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-red-600 hover:border-red-600 hover:shadow-lg transition-all" title="Reject Request">
                        <AlertCircle class="size-4" />
                      </button>

                      <button v-if="role === 'admin' && currentTab === 'asset' && !ticket.issue?.asset && !isResolved(ticket)" @click="openEscalate(ticket)" class="p-3 bg-white border border-gray-100 text-slate-500 rounded-xl hover:text-purple-600 hover:border-purple-600 hover:shadow-lg transition-all" title="Escalate to Management">
                        <ArrowUpRight class="size-4" />
                      </button>
                   </template>
                   <template v-else-if="isRejected(ticket)">
                      <div class="p-3 bg-red-50 text-red-600 rounded-xl border border-red-100 flex items-center gap-2" title="Request Declined">
                        <AlertCircle class="size-4" />
                        <span class="text-[9px] font-black uppercase tracking-widest">Declined</span>
                      </div>
                   </template>

                   <button v-if="role === 'admin'" @click="removeRow(ticket.id)" class="p-3 bg-white border border-gray-100 text-slate-400 rounded-xl hover:text-red-500 hover:border-red-500 hover:shadow-lg transition-all" title="Delete Ticket">
                     <Trash2 class="size-4" />
                   </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && rows.length === 0">
              <td :colspan="role === 'admin' ? 5 : 4" class="p-24 text-center">
                 <div class="flex flex-col items-center gap-4">
                    <div class="size-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-200">
                       <Inbox class="size-8" />
                    </div>
                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em]">No operational data found</p>
                 </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="role === 'admin' && pagination.last_page > 1" class="p-8 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
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
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, watch, onUnmounted } from 'vue'
import axios from 'axios';
import { useWindowFocus } from '@vueuse/core';
import { 
  Search, Edit3, UserPlus, CheckCircle, Package, 
  Trash2, ChevronLeft, ChevronRight, Inbox,
  ShieldCheck, Info, AlertCircle, ArrowUpRight
} from 'lucide-vue-next';
import Loader from '@/components/Loader.vue';

const isFocused = useWindowFocus()
const REFRESH_INTERVAL = 20000
let intervalId = null

const all_rows = ref([])
const loading = ref(false);
const saving = ref(false);
const currentTab = ref('asset');

const filters = reactive({ search: '', priority: '', per_page: 10 })
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 })

const showUpdate = ref(false)
const editingId = ref(null)
const updateForm = reactive({ description: '', priority: 'medium', communication: '' })

const showAssign = ref(false)
const assignTicket = ref(null)
const assignForm = reactive({ asset_id: '', communication: '', accessory_allocations: [] })
const assignOptions = ref([])
const accessoryOptions = ref([])

const showReject = ref(false)
const rejectTicket = ref(null)
const rejectForm = reactive({ reason: '' })

const statuses = ref([])
const assignSearch = ref('')

const showEscalate = ref(false)
const escalateTicket = ref(null)
const escalateForm = reactive({ item_name: '', estimated_cost: '', reason: '' }) 

const role = (() => {
  try { return JSON.parse(localStorage.getItem('user_data') || '{}').role || 'user' } catch { return 'user' }
})()

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      search: filters.search || undefined,
      priority: filters.priority || undefined,
      per_page: filters.per_page,
      page
    };

    if (role === 'admin') {
      const { data } = await axios.get('/api/tickets/list', { params })
      all_rows.value = data.data || []
      pagination.current_page = data.current_page || 1
      pagination.last_page = data.last_page || 1
      pagination.total = data.total || 0
    } else {
      const { data } = await axios.get('/api/my-tickets', { params })
      all_rows.value = data || []
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = all_rows.value.length
    }
  } finally {
    loading.value = false
  }
}

const loadStatuses = async () => {
  try {
    const { data } = await axios.get('/api/statuses');
    statuses.value = data || [];
  } catch (e) {
    console.error('Failed to load statuses', e);
  }
};

const isAssetTicket = (ticket) => {
  const desc = ticket.Description || '';
  return !!(ticket.issue || desc.includes('Request Category:'));
};

const rows = computed(() => {
  if (currentTab.value === 'asset') {
    return all_rows.value.filter(t => isAssetTicket(t));
  } else { 
    return all_rows.value.filter(t => !isAssetTicket(t));
  }
});

const extractSubject = (desc) => {
  if (!desc) return 'General Request';
  const lines = desc.split('\n');
  const subjectLine = lines.find(l => l.toLowerCase().startsWith('subject:'));
  if (subjectLine) return subjectLine.split(':')[1].trim();
  return 'IT Support Query';
};

const cleanDescription = (desc) => {
  if (!desc) return '';
  // Remove Subject:, Request Category:, Details:, and Request Details: lines
  return desc
    .replace(/Subject:.*\n?/i, '')
    .replace(/Request Category:.*\n?/i, '')
    .replace(/Details:.*\n?/i, '')
    .replace(/Request Details:.*\n?/i, '')
    .trim();
};

const tabClass = (tabName) => [
  'px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
  currentTab.value === tabName
    ? 'bg-vilcom-blue text-white shadow-lg shadow-blue-900/10'
    : 'text-gray-400 hover:text-slate-700 hover:bg-gray-50'
];

const openUpdate = (ticket) => {
  editingId.value = ticket.id
  updateForm.description = ticket.Description || ''
  updateForm.priority = ticket.Priority || 'medium'
  updateForm.communication = '' 
  showUpdate.value = true
}

const isResolved = (ticket) => {
  const statusName = ticket.status?.Status_Name?.toLowerCase() || '';
  return ['resolved', 'closed', 'completed'].includes(statusName);
}

const isRejected = (ticket) => {
  const statusName = ticket.status?.Status_Name?.toLowerCase() || '';
  return ['rejected', 'cancelled', 'declined'].includes(statusName);
}

const statusContainerClass = (status) => {
  const s = String(status || '').toLowerCase();
  if (['resolved', 'closed', 'completed'].includes(s)) return 'bg-green-50 text-green-600 border-green-100 shadow-sm shadow-green-900/5';
  if (['rejected', 'cancelled', 'declined'].includes(s)) return 'bg-red-50 text-red-600 border-red-100 shadow-sm shadow-red-900/5';
  if (s === 'pending' || s === 'new' || s === 'open') return 'bg-orange-50 text-vilcom-orange border-orange-100 shadow-sm shadow-orange-900/5';
  if (s === 'in progress') return 'bg-blue-50 text-vilcom-blue border-blue-100 shadow-sm shadow-blue-900/5';
  return 'bg-gray-50 text-gray-500 border-gray-100';
}

const statusDotClass = (status) => {
  const s = String(status || '').toLowerCase();
  if (['resolved', 'closed', 'completed'].includes(s)) return 'bg-green-500';
  if (['rejected', 'cancelled', 'declined'].includes(s)) return 'bg-red-500';
  if (s === 'pending' || s === 'new' || s === 'open') return 'bg-vilcom-orange';
  if (s === 'in progress') return 'bg-vilcom-blue';
  return 'bg-gray-400';
}

const resolveTicket = async (ticket) => {
  if (!confirm('Mark this support ticket as resolved?')) return;
  try {
    await axios.put(`/api/tickets/${ticket.id}`, {
      action: 'resolve',
      communication: 'Issue has been finalized and resolved by IT support team.'
    });
    alert('Ticket resolution committed.');
    fetchRows(pagination.current_page);
  } catch (err) {
    alert('Failed to finalize resolution.');
  }
}

const saveUpdate = async () => {
  if (!editingId.value) return;
  saving.value = true
  try {
    await axios.put(`/api/tickets/${editingId.value}`, {
      description: updateForm.description,
      priority: updateForm.priority,
      communication: updateForm.communication
    })
    showUpdate.value = false
    editingId.value = null
    fetchRows(pagination.current_page)
  } catch (err) {
    console.error("Update failed", err);
  } finally {
    saving.value = false
  }
}

const extractRequestedCategory = (ticket) => {
  const text = String(ticket?.Description || '')
  const line = text.split('\n').find((l) => l.toLowerCase().startsWith('request category:'))
  return line ? line.split(':').slice(1).join(':').trim() : ''
}

const extractRejectionReason = (log) => {
  if (!log) return 'No reason provided.';
  const lines = log.split('\n');
  const rejectLine = lines.reverse().find(l => l.includes('REQUEST REJECTED. Reason:'));
  if (rejectLine) return rejectLine.split('Reason:')[1].trim();
  return 'Declined by administration.';
}

const openAssign = async (ticket) => {
  assignTicket.value = ticket
  assignForm.asset_id = ''
  assignForm.communication = ''
  assignForm.accessory_allocations = []
  showAssign.value = true

  const category = extractRequestedCategory(ticket)
  assignSearch.value = category 
  
  await performAssignSearch()

  const { data } = await axios.get('/api/accessories/list', { params: { per_page: 100 } });
  accessoryOptions.value = (data?.data || []).filter((a) => Number(a.remaining_qty) > 0)
}

const openReject = (ticket) => {
  rejectTicket.value = ticket
  rejectForm.reason = ''
  showReject.value = true
}

const submitReject = async () => {
  if (!rejectTicket.value) return
  if (!rejectForm.reason) {
    alert('Please provide a reason for rejection.')
    return
  }
  
  try {
    await axios.post(`/api/tickets/${rejectTicket.value.id}/reject`, {
      reason: rejectForm.reason
    })
    showReject.value = false
    alert('Request declined.')
    fetchRows(pagination.current_page)
  } catch (err) {
    console.error('Rejection failed:', err)
    alert('Failed to reject request.')
  }
}

const addAccessoryRow = () => { assignForm.accessory_allocations.push({ id: '', qty: 1 }) }
const removeAccessoryRow = (index) => { assignForm.accessory_allocations.splice(index, 1) }

const performAssignSearch = async () => {
  const { data } = await axios.get('/api/assets/list', {
    params: { 
      search: assignSearch.value || undefined, 
      available: true, 
      per_page: 100 
    }
  })
  assignOptions.value = data?.data || []
}

watch(assignSearch, () => {
  performAssignSearch()
})

const submitAssign = async () => {
  if (!assignTicket.value) return
  await axios.post(`/api/tickets/${assignTicket.value.id}/assign-asset`, {
    asset_id: Number(assignForm.asset_id),
    communication: assignForm.communication || 'Hardware allocated and deployed per ticket request.',
    accessory_allocations: assignForm.accessory_allocations
      .filter((x) => x.id && Number(x.qty) > 0)
      .map((x) => ({ id: Number(x.id), qty: Number(x.qty) })),
  })
  showAssign.value = false
  fetchRows(pagination.current_page)
}

const removeRow = async (id) => {
  if (role !== 'admin') return
  if (!confirm('Permanent delete this record?')) return
  await axios.delete(`/api/tickets/${id}`)
  fetchRows(pagination.current_page)
}

const priorityClass = (p) => {
  const s = String(p || '').toLowerCase();
  if (s === 'high') return 'bg-red-50 text-red-600 border-red-100';
  if (s === 'medium') return 'bg-orange-50 text-vilcom-orange border-orange-100';
  return 'bg-blue-50 text-vilcom-blue border-blue-100';
}

watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})

onMounted(() => {
  fetchRows()
  if (role === 'admin') {
    loadStatuses();
  }
  intervalId = setInterval(() => fetchRows(pagination.current_page), REFRESH_INTERVAL)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>
