<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">
          {{ headingText.split(' ')[0] }} <span class="text-vilcom-blue">{{ headingText.split(' ').slice(1).join(' ') }}</span>
        </h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Monitoring asset mobility and chain of custody
        </p>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30">
        <div class="relative group">
          <input 
            v-model="filters.search" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-64 shadow-sm" 
            placeholder="Search movements..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.status" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
          <option value="">All Statuses</option>
          <option value="pending">Pending Approval</option>
          <option value="pending_inspection">Pending Inspection</option>
          <option value="inspected">Inspected</option>
          <option value="accepted">Accepted / Done</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Asset Detail</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Flow Type</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Stakeholders</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Protocol</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Scanning Movement Logs...
              </td>
            </tr>
            <tr v-for="item in filteredTransfers" :key="item.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <ArrowLeftRight class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ item.asset?.model || 'Mixed Batch' }}
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono">
                      {{ item.asset?.serial || 'VARIOUS' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">{{ item.type || 'Transfer' }}</span>
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-col gap-1">
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                    <span class="text-slate-600 font-black">FROM:</span> {{ item.sender?.name }}
                  </div>
                  <div v-if="!isReturnMode" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                    <span class="text-vilcom-blue font-black">TO:</span> {{ item.receiver?.name || 'ADMIN' }}
                  </div>
                  <div v-else class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                     <span class="text-vilcom-orange font-black">REASON:</span> {{ item.reason || 'N/A' }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 text-center">
                <span :class="statusClass(item.status)" class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest ring-1 ring-white/50">
                  {{ item.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2">
                  <template v-if="item.status === 'pending_inspection'">
                    <button @click="openInspectionModal(item)" :disabled="processing" class="px-4 py-2 bg-vilcom-blue text-white rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-blue-900/10 hover:scale-105 active:scale-95 transition-all">
                      INSPECT
                    </button>
                  </template>

                  <template v-else-if="item.status === 'inspected'">
                    <button v-if="!item.receiver || item.type !== 'transfer'" @click="updateStatus(item.id, 'accepted')" :disabled="processing" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-600 hover:text-white transition-all">
                      <CheckCircle2 class="size-4" />
                    </button>
                    <button v-if="!item.receiver || item.type !== 'transfer'" @click="updateStatus(item.id, 'rejected')" :disabled="processing" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                      <XCircle class="size-4" />
                    </button>
                    <span v-else class="text-[9px] font-black text-blue-400 uppercase italic">Awaiting Recipient</span>
                  </template>

                  <template v-else-if="item.status === 'pending'">
                    <button @click="updateStatus(item.id, 'approved')" :disabled="processing" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-600 hover:text-white transition-all">
                      <CheckCircle2 class="size-4" />
                    </button>
                    <button @click="updateStatus(item.id, 'rejected')" :disabled="processing" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all">
                      <XCircle class="size-4" />
                    </button>
                  </template>
                  
                  <span v-else class="text-[9px] font-black text-gray-300 uppercase">Processed</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="p-8 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
          Sector {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Total Events: {{ pagination.total }}
        </div>
        <div class="flex items-center gap-3">
          <button :disabled="pagination.current_page <= 1" @click="fetchTransfers(pagination.current_page - 1)" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronLeft class="size-4" />
          </button>
          <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchTransfers(pagination.current_page + 1)" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronRight class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Inspection Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-vilcom-orange text-white rounded-2xl shadow-lg shadow-orange-900/20">
              <Search class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">Asset Verification</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Post-Movement Inspection Protocol</p>
            </div>
          </div>

          <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 space-y-2">
            <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Target Unit</div>
            <div class="text-sm font-black text-slate-800">{{ selectedItem.asset?.model }}</div>
            <div class="text-[10px] font-bold text-slate-500 font-mono">{{ selectedItem.asset?.serial }}</div>
          </div>

          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Physical Integrity</label>
              <select v-model="inspectionForm.condition" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="New">New / Mint</option>
                <option value="Good">Good (Functional)</option>
                <option value="Fair">Fair (Wear/Tear)</option>
                <option value="Damaged">Damaged (Repair Required)</option>
                <option value="Broken">Broken (Critical Failure)</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Deployment Readiness</label>
              <select v-model="inspectionForm.disposition" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
                <option value="ready_to_deploy">Ready to Deploy</option>
                <option value="non_deployable">Non-Deployable</option>
                <option value="maintenance">Maintenance Queue</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Technical Findings</label>
              <textarea v-model="inspectionForm.admin_notes" rows="3" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="Enter inspection notes..."></textarea>
            </div>
          </div>

          <div class="flex gap-4">
            <button @click="submitInspection" :disabled="processing" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
              {{ processing ? 'Syncing...' : 'Certify Inspection' }}
            </button>
            <button @click="showModal = false" class="px-8 py-4 bg-gray-100 text-gray-400 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import Loader from '@/components/Loader.vue';
import eventBus from '@/eventBus';
import { Search, ChevronLeft, ChevronRight, Plus, Filter, Edit3, Trash2, UserPlus, Info, ArrowLeftRight, CheckCircle2, XCircle } from 'lucide-vue-next';

const filters = reactive({
  search: '',
  status: '',
});


const props = defineProps({
  mode: {
    type: String,
    default: 'all',
  },
  title: {
    type: String,
    default: '',
  },
});

const transfers = ref([]);
const loading = ref(false);
const showModal = ref(false);
const selectedItem = ref({});

const inspectionForm = ref({
  condition: 'Good',
  disposition: 'ready_to_deploy',
  missing_items_text: '',
  admin_notes: '',
});

const normalizeType = (value) => String(value || '').toLowerCase();

const headingText = computed(() => {
  if (props.title) return props.title;
  if (props.mode === 'transfer') return 'Asset Transfer Requests';
  if (props.mode === 'return') return 'Asset Return Requests';
  return 'Asset Transfer & Return Requests';
});

const isTransferMode = computed(() => props.mode === 'transfer');
const isReturnMode = computed(() => props.mode === 'return');

const filteredTransfers = computed(() => {
  let items = transfers.value;

  if (props.mode === 'transfer') {
    items = items.filter((item) => normalizeType(item.type) !== 'return');
  } else if (props.mode === 'return') {
    items = items.filter((item) => normalizeType(item.type) === 'return');
  }

  if (filters.search) {
    const s = filters.search.toLowerCase();
    items = items.filter(i => 
      (i.asset?.model?.toLowerCase().includes(s)) ||
      (i.asset?.serial?.toLowerCase().includes(s)) ||
      (i.sender?.name?.toLowerCase().includes(s)) ||
      (i.receiver?.name?.toLowerCase().includes(s))
    );
  }

  if (filters.status) {
    items = items.filter(i => i.status === filters.status);
  }

  return items;
});


const fetchTransfers = async (page = 1) => {
  loading.value = true;
  try {
    let endpoint = '/api/transfers';
    
    if (props.mode === 'return') {
      endpoint = '/api/return-requests';
    }

    if (page > 1) endpoint += (endpoint.includes('?') ? '&' : '?') + `page=${page}`;
    
    const res = await axios.get(endpoint);
    transfers.value = res.data.data || res.data;
    pagination.value = res.data;
  } catch (error) {
    console.error('Failed to fetch transfers', error);
  } finally {
    loading.value = false;
  }
};

const pagination = ref(null);

const openInspectionModal = (item) => {
  selectedItem.value = item;
  inspectionForm.value = {
    condition: 'Good',
    disposition: 'ready_to_deploy',
    missing_items_text: (item.missing_items || []).join(', '),
    admin_notes: '',
  }
  showModal.value = true;
};

const submitInspection = async () => {
  processing.value = true;
  try {
    const endpoint = props.mode === 'return'
      ? `/api/admin/return-requests/${selectedItem.value.id}/complete`
      : `/api/admin/transfers/${selectedItem.value.id}/complete`;

    const res = await axios.post(endpoint, {
      condition: inspectionForm.value.condition,
      disposition: inspectionForm.value.disposition,
      missing_items: (inspectionForm.value.missing_items_text || '')
        .split(',')
        .map(v => v.trim())
        .filter(Boolean),
      admin_notes: inspectionForm.value.admin_notes || null,
    });

    const updated = res.data.transfer;
    const idx = transfers.value.findIndex(t => t.id === selectedItem.value.id);
    if (idx !== -1 && updated) {
      transfers.value[idx] = updated;
    }

    showModal.value = false;
    alert("Item check saved successfully.");
    eventBus.emit('transfer-changed', updated);
  } catch (err) {
    alert("Error saving inspection. Check backend logs.");
  } finally {
    processing.value = false;
  }
};

const removeTransfer = async (id) => {
  if (!confirm('Delete this transfer request?')) return
  const endpoint = props.mode === 'return' ? `/api/return-requests/${id}` : `/api/transfers/${id}`;
  await axios.delete(endpoint)
  fetchTransfers()
}

const updateStatus = async (id, status) => {
  if (!confirm(`Confirm ${status}?`)) return;
  processing.value = true;
  try {
    const endpoint = props.mode === 'return' ? `/api/return-requests/${id}/status` : `/api/transfers/${id}/status`;
    const payload = { status };
    if (props.mode === 'return' && status === 'rejected') {
      const reason = prompt('Rejection reason (optional):');
      if (reason === null) {
        processing.value = false;
        return;
      }
      payload.reason = reason;
    }
    const res = await axios.put(endpoint, payload);
    const updated = res.data.transfer || res.data.return_request;

    if (updated) {
      const idx = transfers.value.findIndex(t => t.id === id);
      if (idx !== -1) {
        transfers.value.splice(idx, 1, updated);
      }
      eventBus.emit('transfer-changed', updated);
    }
  } catch (err) {
    alert("Update failed.");
  } finally {
    processing.value = false;
  }
};

const statusClass = (status) => {
  const classes = {
    'pending': 'bg-orange-100 text-orange-700',
    'pending_inspection': 'bg-indigo-100 text-indigo-700',
    'inspected': 'bg-blue-100 text-blue-700',
    'accepted': 'bg-green-100 text-green-700',
    'pending_verification': 'bg-blue-100 text-blue-700',
    'approved': 'bg-green-100 text-green-700',
    'completed': 'bg-green-100 text-green-700',
    'rejected': 'bg-red-100 text-red-700',
    'closed': 'bg-gray-200 text-gray-700', // Added style for closed tickets
    'lost': 'bg-gray-800 text-white',
  };
  return classes[status] || 'bg-gray-100 text-gray-600';
};

onMounted(() => {
  fetchTransfers();
  eventBus.on('transfer-changed', fetchTransfers);
});

const processing = ref(false);
const components = { Loader };
</script>