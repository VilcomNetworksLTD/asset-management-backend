<template>
  <div class="p-8 space-y-10 font-inter">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Purchase <span class="text-vilcom-blue">Escalations</span></h1>
        <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Review staff asset requests and escalate to management for budget approval.</p>
      </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
       <div v-for="stat in summaryStats" :key="stat.label" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center gap-4">
         <div :class="['p-4 rounded-2xl shadow-inner', stat.color]">
            <component :is="stat.icon" class="size-5 text-white" />
         </div>
         <div>
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ stat.label }}</div>
            <div class="text-xl font-black text-slate-800">{{ stat.count }}</div>
         </div>
       </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm">
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
          <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Filter by Status:</span>
          <select v-model="statusFilter" class="bg-slate-50 border-none rounded-xl py-2 px-4 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="escalated">Escalated</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="purchased">Purchased</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Filter by Type:</span>
          <select v-model="typeFilter" class="bg-slate-50 border-none rounded-xl py-2 px-4 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20">
            <option value="all">All Types</option>
            <option value="asset_request">Asset Request</option>
            <option value="maintenance_part">Maintenance Part</option>
          </select>
        </div>
        <button @click="clearFilters" class="ml-auto text-xs font-bold text-vilcom-blue hover:text-blue-700 transition-colors">
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Pending Staff Requests -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-8 border-b border-slate-100 bg-slate-50/30">
        <h2 class="text-lg font-black text-slate-800 tracking-tight">Staff Acquisition Requests</h2>
        <p class="text-xs text-gray-400 mt-1">Requests from staff members that need admin review before escalation to management</p>
      </div>

      <div v-if="loading" class="p-20 text-center">
         <div class="size-8 border-4 border-vilcom-blue/10 border-t-vilcom-blue rounded-full animate-spin mx-auto"></div>
      </div>

      <table class="w-full text-left border-collapse" v-else>
        <thead>
          <tr class="bg-slate-50/50">
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Requester</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Asset Owner</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Item Details</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Justification</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Requested On</th>
            <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
            <th class="p-8 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="req in filteredRequests" :key="req.id" class="group hover:bg-slate-50/50 transition-colors">
            <td class="p-6">
              <div class="flex items-center gap-4">
                <div class="size-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold uppercase text-xs">
                  {{ req.requester?.name?.charAt(0) }}
                </div>
                <div>
                   <div class="text-sm font-black text-slate-800">{{ req.requester?.name }}</div>
                   <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">{{ req.source_type_label }}</div>
                </div>
              </div>
            </td>
            <td class="p-6">
              <div class="text-sm font-bold text-slate-700">{{ req.asset_owner_name || 'N/A' }}</div>
            </td>
            <td class="p-6">
              <div class="text-sm font-bold text-slate-700">{{ req.item_name }}</div>
              <div v-if="req.estimated_cost" class="text-[10px] text-vilcom-blue mt-1">Est: ${{ req.estimated_cost }}</div>
            </td>
            <td class="p-6">
              <div class="text-xs text-gray-500 max-w-[200px] truncate">{{ req.description }}</div>
            </td>
            <td class="p-6">
              <div class="text-xs text-gray-400">{{ formatDate(req.created_at) }}</div>
            </td>
            <td class="p-6 text-center">
              <span :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-[0.15em]', statusStyles[req.status] || 'bg-gray-50 text-gray-500']">
                {{ req.status }}
              </span>
            </td>
            <td class="p-8 text-right">
              <div class="flex items-center justify-end gap-3">
                 <button v-if="req.status === 'pending'" @click="openEscalateModal(req)" class="p-3 bg-vilcom-blue text-white rounded-xl hover:bg-blue-700 transition-all shadow-sm" title="Escalate to Management">
                   <Send class="size-4" />
                 </button>
                 <button @click="viewDetails(req)" class="p-3 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-all shadow-sm" title="View Details">
                   <Eye class="size-4" />
                 </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && filteredRequests.length === 0">
             <td colspan="6" class="p-20 text-center font-bold text-slate-300 italic">No acquisition requests match the current filters.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Escalation Modal -->
    <transition name="modal">
      <div v-if="showEscalateModal" class="fixed inset-0 z-[3000] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEscalateModal = false"></div>
        <div class="relative bg-white w-full max-w-xl rounded-[3rem] shadow-2xl p-10 overflow-hidden border border-slate-100">
           <div class="absolute top-0 right-0 p-8">
              <button @click="showEscalateModal = false" class="text-slate-300 hover:text-red-500 transition-colors"><X class="size-6" /></button>
           </div>
           
           <div class="flex items-center gap-4 mb-8">
              <div class="p-4 bg-vilcom-blue rounded-2xl text-white">
                 <Send class="size-6" />
              </div>
              <div>
                <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Escalate to Management</h3>
                <p class="text-xs text-gray-400">Forward this request for budget approval</p>
              </div>
           </div>
           
           <div class="space-y-6">
              <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                 <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Requested Item</div>
                 <div class="text-lg font-black text-slate-800">{{ activeRequest?.item_name }}</div>
                 <div v-if="activeRequest?.description" class="text-sm text-gray-500 mt-2">{{ activeRequest.description }}</div>
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Estimated Budget</label>
                <div class="relative">
                  <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 font-black">$</span>
                  <input v-model="escalationForm.estimated_cost" type="number" class="w-full bg-slate-50 border-none rounded-2xl py-4 pl-10 pr-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="0.00">
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Admin Notes (Optional)</label>
                <textarea v-model="escalationForm.notes" rows="3" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="Add operational context for management..."></textarea>
              </div>
           </div>

           <div class="mt-10 flex gap-4">
              <button @click="submitEscalation" :disabled="escalating" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:bg-blue-700 transition-all disabled:opacity-50">
                {{ escalating ? 'Escalating...' : 'Escalate to Management' }}
              </button>
           </div>
        </div>
      </div>
    </transition>

    <!-- Details Modal -->
    <transition name="modal">
      <div v-if="showDetailsModal" class="fixed inset-0 z-[3000] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDetailsModal = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[3rem] shadow-2xl p-10 overflow-hidden border border-slate-100">
           <div class="absolute top-0 right-0 p-8">
              <button @click="showDetailsModal = false" class="text-slate-300 hover:text-red-500 transition-colors"><X class="size-6" /></button>
           </div>
           
           <h3 class="text-xl font-black text-slate-800 mb-6">Request Details</h3>
           
           <div class="space-y-4">
              <div class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Requester</span>
                 <span class="text-sm font-bold text-slate-700">{{ activeRequest?.requester?.name }}</span>
              </div>
              <div class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Item Name</span>
                 <span class="text-sm font-bold text-slate-700">{{ activeRequest?.item_name }}</span>
              </div>
              <div class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Type</span>
                 <span class="text-sm font-bold text-slate-700 capitalize">{{ activeRequest?.type?.replace('_', ' ') }}</span>
              </div>
              <div class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Estimated Cost</span>
                 <span class="text-sm font-bold text-slate-700">{{ activeRequest?.estimated_cost ? '$' + activeRequest.estimated_cost : 'Not specified' }}</span>
              </div>
              <div class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Status</span>
                 <span :class="['px-3 py-1 rounded-lg text-[9px] font-black uppercase', statusStyles[activeRequest?.status] || 'bg-gray-50 text-gray-500']">{{ activeRequest?.status }}</span>
              </div>
              <div v-if="activeRequest?.management" class="flex justify-between py-3 border-b border-slate-100">
                 <span class="text-xs font-black text-gray-400 uppercase">Reviewed By</span>
                 <span class="text-sm font-bold text-slate-700">{{ activeRequest.management.name }}</span>
              </div>
              <div v-if="activeRequest?.rejection_reason" class="py-3">
                 <span class="text-xs font-black text-gray-400 uppercase block mb-2">Rejection Reason</span>
                 <p class="text-sm text-gray-600 bg-slate-50 p-4 rounded-xl">{{ activeRequest.rejection_reason }}</p>
              </div>
              <div class="py-3">
                 <span class="text-xs font-black text-gray-400 uppercase block mb-2">Justification</span>
                 <p class="text-sm text-gray-600 bg-slate-50 p-4 rounded-xl">{{ activeRequest?.description }}</p>
              </div>
           </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Send, Eye, X, AlertCircle, CheckCircle, Clock, Package } from 'lucide-vue-next';

const requests = ref([]);
const loading = ref(false);
const escalating = ref(false);
const showEscalateModal = ref(false);
const showDetailsModal = ref(false);
const activeRequest = ref(null);
const statusFilter = ref('all');
const typeFilter = ref('all');

const escalationForm = ref({
  estimated_cost: '',
  notes: ''
});

const statusStyles = {
  pending: 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
  escalated: 'bg-blue-50 text-vilcom-blue ring-1 ring-blue-100',
  approved: 'bg-green-50 text-green-600 ring-1 ring-green-100',
  rejected: 'bg-red-50 text-red-500 ring-1 ring-red-100',
  purchased: 'bg-purple-50 text-purple-600 ring-1 ring-purple-100'
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/admin/purchase-requests');
    requests.value = data;
  } finally {
    loading.value = false;
  }
};

const filteredRequests = computed(() => {
  return requests.value.filter(r => {
    const matchesStatus = statusFilter.value === 'all' || r.status === statusFilter.value;
    const matchesType = typeFilter.value === 'all' || r.type === typeFilter.value;
    return matchesStatus && matchesType;
  });
});

const summaryStats = computed(() => {
  const pending = requests.value.filter(r => r.status === 'pending').length;
  const escalated = requests.value.filter(r => r.status === 'escalated').length;
  const approved = requests.value.filter(r => r.status === 'approved').length;
  return [
    { label: 'Total Requests', count: requests.value.length, icon: Package, color: 'bg-slate-700' },
    { label: 'Pending Review', count: pending, icon: Clock, color: 'bg-vilcom-orange' },
    { label: 'Escalated', count: escalated, icon: Send, color: 'bg-vilcom-blue' },
    { label: 'Approved', count: approved, icon: CheckCircle, color: 'bg-green-600' },
  ];
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const openEscalateModal = (request) => {
  activeRequest.value = request;
  escalationForm.value = {
    estimated_cost: request.estimated_cost || '',
    notes: ''
  };
  showEscalateModal.value = true;
};

const viewDetails = (request) => {
  activeRequest.value = request;
  showDetailsModal.value = true;
};

const clearFilters = () => {
  statusFilter.value = 'all';
  typeFilter.value = 'all';
};

const submitEscalation = async () => {
  escalating.value = true;
  try {
    await axios.post(`/api/purchase-requests/${activeRequest.value.id}/escalate`, {
      estimated_cost: escalationForm.value.estimated_cost,
      notes: escalationForm.value.notes
    });
    showEscalateModal.value = false;
    fetchRequests();
    alert('Request successfully escalated to management.');
  } catch (err) {
    console.error('Escalation failed:', err);
    alert('Failed to escalate request. Please try again.');
  } finally {
    escalating.value = false;
  }
};

onMounted(fetchRequests);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease-out; }
.modal-enter-from { opacity: 0; transform: scale(0.9); }
.modal-leave-to { opacity: 0; transform: scale(0.9); }
</style>
