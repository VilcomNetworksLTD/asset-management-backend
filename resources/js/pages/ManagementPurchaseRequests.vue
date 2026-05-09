<template>
  <div class="p-4 md:p-0 space-y-10 font-inter">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight text-wrap">Aquisition <span class="text-vilcom-orange">Control</span></h1>
        <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Review and authorize asset purchases and maintenance part requests.</p>
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

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 items-center">
      <select v-model="statusFilter" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[140px] shadow-sm">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="purchased">Purchased</option>
      </select>
      <button @click="fetchRequests" class="bg-slate-800 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-black/5">Apply Filter</button>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden relative min-h-[400px]">
      <div v-if="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
         <div class="size-12 border-4 border-vilcom-blue/10 border-t-vilcom-blue rounded-full animate-spin"></div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1200px]">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="p-8 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Requester</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Asset Owner</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Item Details</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Source</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Workflow Type</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em]">Est. Cost</th>
              <th class="p-6 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="p-8 font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="req in requests" :key="req.id" class="group hover:bg-slate-50/50 transition-colors">
              <td class="p-8">
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
                <div class="text-[10px] text-gray-400 mt-1 truncate max-w-[200px]">{{ req.description || 'No additional parameters' }}</div>
              </td>
              <td class="p-6">
                <div v-if="req.ticket_id" class="flex items-center gap-2">
                  <span class="px-3 py-1.5 rounded-lg text-[9px] font-black bg-purple-50 text-purple-600 uppercase tracking-widest">{{ req.source_type_label }}</span>
                </div>
                <div v-else-if="req.maintenance_id" class="flex items-center gap-2">
                  <span class="px-3 py-1.5 rounded-lg text-[9px] font-black bg-orange-50 text-orange-600 uppercase tracking-widest">{{ req.source_type_label }}</span>
                </div>
                <div v-else class="flex items-center gap-2">
                  <span class="px-3 py-1.5 rounded-lg text-[9px] font-black bg-gray-50 text-gray-600 uppercase tracking-widest">{{ req.source_type_label }}</span>
                </div>
              </td>
              <td class="p-6">
                <span class="px-3 py-1.5 rounded-lg text-[9px] font-black bg-blue-50 text-vilcom-blue uppercase tracking-widest">{{ req.type.replace('_', ' ') }}</span>
              </td>
              <td class="p-6">
                <div class="text-sm font-black text-slate-800">{{ req.estimated_cost ? 'KSh ' + req.estimated_cost : 'TBD' }}</div>
                <div v-if="req.status === 'rejected' && req.rejection_reason" class="mt-3 group/reason relative">
                   <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-red-100 hover:bg-red-600 hover:text-white transition-all cursor-default">
                      <AlertCircle class="size-3" />
                      Feedback
                   </div>
                   <div class="hidden group-hover/reason:block absolute z-20 mt-2 p-4 bg-white shadow-2xl border border-red-100 rounded-2xl w-64 text-[10px] font-bold text-slate-600 leading-relaxed right-0 top-full">
                      <div class="text-red-600 uppercase tracking-widest font-black mb-2 flex items-center gap-2">
                         <div class="size-1 bg-red-600 rounded-full"></div>
                         Official Reason
                      </div>
                      {{ req.rejection_reason }}
                   </div>
                </div>
              </td>
              <td class="p-6 text-center">
                <span :class="['px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-[0.15em]', statusStyles[req.status]]">
                  {{ req.status }}
                </span>
              </td>
              <td class="p-8 text-right">
                <div v-if="req.status === 'pending'" class="flex items-center justify-end gap-3">
                   <button @click="openDecision(req, 'approved')" class="p-3 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Authorize Acquisition">
                     <Check class="size-4" />
                   </button>
                   <button @click="openDecision(req, 'rejected')" class="p-3 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Deny Request">
                     <X class="size-4" />
                   </button>
                </div>
                <div v-else-if="req.status === 'approved'" class="text-[10px] font-black text-blue-500 uppercase tracking-widest">
                   Authorized by {{ req.management?.name || 'VNL Admin' }}
                </div>
                <div v-else-if="req.status === 'rejected'" class="text-[10px] font-black text-red-400 uppercase tracking-widest line-through">
                   Deployment Terminated
                </div>
              </td>
            </tr>
            <tr v-if="!loading && requests.length === 0">
               <td colspan="8" class="p-20 text-center font-bold text-slate-300 italic">No acquisition requests currently in queue.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Decision Modal -->
    <transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-[3000] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white w-full max-w-xl rounded-[3rem] shadow-2xl p-10 overflow-hidden border border-slate-100">
           <div class="absolute top-0 right-0 p-8">
              <button @click="showModal = false" class="text-slate-300 hover:text-red-500 transition-colors"><X class="size-6" /></button>
           </div>
           
           <h3 class="text-2xl font-black text-slate-800 mb-8 tracking-tighter">
             {{ currentDecision === 'approved' ? 'Acquisition Authorization' : 'Request Termination' }}
           </h3>
           
           <div class="space-y-6">
              <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                 <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Target Item</div>
                 <div class="text-lg font-black text-slate-800">{{ activeRequest?.item_name }}</div>
              </div>

              <div class="space-y-2" v-if="currentDecision === 'approved'">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Authorized Budget Limit</label>
                <div class="relative">
                  <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 font-black text-[10px]">KSh</span>
                  <input v-model="decisionForm.estimated_cost" type="number" class="w-full bg-slate-50 border-none rounded-2xl py-4 pl-10 pr-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="0.00">
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">{{ currentDecision === 'approved' ? 'Authorization Addendum' : 'Protocol Violation / Reason' }}</label>
                <textarea v-model="decisionForm.reason" rows="4" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20" placeholder="Operational notes for the logistics department..."></textarea>
              </div>
           </div>

           <div class="mt-10 flex gap-4">
              <button @click="submitDecision" :disabled="submitting" :class="['flex-1 py-4 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl transition-all hover:scale-105 active:scale-95', currentDecision === 'approved' ? 'bg-green-600 shadow-green-900/20' : 'bg-red-600 shadow-red-900/20']">
                {{ submitting ? 'Processing...' : (currentDecision === 'approved' ? 'Validate Authorization' : 'Execute Rejection') }}
              </button>
           </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { 
  Check, X, FileText, ShoppingCart, 
  AlertCircle, Package, Layers, ShieldCheck 
} from 'lucide-vue-next';

const requests = ref([]);
const loading = ref(false);
const submitting = ref(false);
const showModal = ref(false);
const activeRequest = ref(null);
const currentDecision = ref(null);
const statusFilter = ref('');

const decisionForm = ref({
  estimated_cost: '',
  reason: ''
});

const statusStyles = {
  pending: 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
  approved: 'bg-green-50 text-green-600 ring-1 ring-green-100',
  rejected: 'bg-red-50 text-red-500 ring-1 ring-red-100',
  purchased: 'bg-blue-50 text-vilcom-blue ring-1 ring-blue-100'
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const params = {};
    if (statusFilter.value) {
      params.status = statusFilter.value;
    }
    const { data } = await axios.get('/api/purchase-requests', { params });
    requests.value = data;
  } finally {
    loading.value = false;
  }
};

const summaryStats = computed(() => {
  return [
    { label: 'Total Inbound', count: requests.value.length, icon: Layers, color: 'bg-slate-700' },
    { label: 'Pending Review', count: requests.value.filter(r => r.status === 'pending').length, icon: AlertCircle, color: 'bg-vilcom-orange' },
    { label: 'Authorized', count: requests.value.filter(r => r.status === 'approved' || r.status === 'purchased').length, icon: ShieldCheck, color: 'bg-green-600' },
    { label: 'Acquired', count: requests.value.filter(r => r.status === 'purchased').length, icon: Package, color: 'bg-vilcom-blue' },
  ];
});

const openDecision = (request, type) => {
  activeRequest.value = request;
  currentDecision.value = type;
  decisionForm.value = {
    estimated_cost: request.estimated_cost || '',
    reason: request.rejection_reason || ''
  };
  showModal.value = true;
};

const submitDecision = async () => {
  submitting.value = true;
  try {
    await axios.put(`/api/purchase-requests/${activeRequest.value.id}/status`, {
      status: currentDecision.value,
      rejection_reason: decisionForm.value.reason,
      estimated_cost: decisionForm.value.estimated_cost
    });
    showModal.value = false;
    fetchRequests();
  } finally {
    submitting.value = false;
  }
};

onMounted(fetchRequests);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease-out; }
.modal-enter-from { opacity: 0; transform: scale(0.9); }
.modal-leave-to { opacity: 0; transform: scale(0.9); }
</style>
