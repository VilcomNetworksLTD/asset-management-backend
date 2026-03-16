<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">{{ headingText }}</h1>
      
      <div v-if="isReturnMode" class="flex border rounded-lg overflow-hidden bg-white shadow-sm">
        <button
          @click="setViewMode('pending')"
          :class="viewMode === 'pending' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-500 hover:bg-gray-50'"
          class="px-4 py-2 text-sm transition-colors"
        >
          Pending Requests
        </button>
        <button
          @click="setViewMode('history')"
          :class="viewMode === 'history' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-500 hover:bg-gray-50'"
          class="px-4 py-2 text-sm border-l transition-colors"
        >
          History (Closed)
        </button>
      </div>
    </div>

    <div class="bg-white rounded shadow-sm overflow-hidden border">
      <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
          <tr class="text-[11px] uppercase text-gray-500 font-bold">
            <th class="p-4">Asset</th>
            <th class="p-4">Type</th>
            <th class="p-4">From</th>
            <th v-if="!isReturnMode" class="p-4">To</th>
            <th v-else class="p-4">Return Details</th>
            <th class="p-4 text-center">Status</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y text-sm">
          <tr v-if="loading">
            <td :colspan="isReturnMode || !isReturnMode ? 7 : 7" class="p-6 text-center">
              <Loader />
            </td>
          </tr>

          <tr v-for="item in filteredTransfers" :key="item.id" class="hover:bg-gray-50">
            <td class="p-4 font-medium">
              <div>
                <template v-if="item.asset">
                  {{ item.asset.model }}
                  <div class="text-[10px] text-gray-400 font-mono">{{ item.asset.serial }}</div>
                </template>
                <template v-else>
                  <span class="italic text-gray-600">Mixed Items</span>
                </template>
              </div>
              <div v-if="item.items && item.items.length" class="text-[10px] text-gray-500">
                <div class="font-bold">Included items:</div>
                <ul class="list-disc list-inside">
                  <li v-for="itm in item.items" :key="itm.type + itm.id">
                    <span class="capitalize">{{ itm.type }}:</span> {{ itm.name || itm.type }}
                  </li>
                </ul>
              </div>
            </td>
            <td class="p-4">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                {{ item.type || 'Transfer' }}
              </span>
            </td>
            <td class="p-4">{{ item.sender?.name }}</td>
            <td v-if="!isReturnMode" class="p-4">{{ item.receiver?.name || 'Admin / Office' }}</td>
            <td v-else class="p-4">
              <div class="text-xs text-gray-700 mb-1">
                <strong>Condition:</strong> {{ item.sender_condition || 'N/A' }}
              </div>
              <div class="text-xs text-gray-500 mb-1">
                <strong>Missing:</strong>
                {{ Array.isArray(item.missing_items) && item.missing_items.length ? item.missing_items.join(', ') : 'None' }}
              </div>
              <div v-if="item.items && item.items.length" class="mb-1">
                <strong class="text-xs text-gray-500">Included:</strong>
                <div class="ml-3 mt-1 space-y-1">
                  <div v-for="itm in item.items" :key="itm.id || itm.type" class="text-xs text-gray-600">
                    • <span class="font-medium text-gray-700 capitalize">{{ itm.type }}</span>: {{ itm.name || itm.type }}
                  </div>
                </div>
              </div>
              <div class="text-xs text-gray-500 italic" v-if="item.issue_notes || item.notes">
                {{ item.issue_notes || item.notes }}
              </div>
            </td>
            <td class="p-4 text-center">
              <span :class="statusClass(item.status)" class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                {{ item.status.replace('_', ' ') }}
              </span>
            </td>
            <td class="p-4 text-right space-x-2">
              <template v-if="item.status === 'pending_inspection'">
                <button @click="openInspectionModal(item)" :disabled="processing" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                  INSPECT
                </button>
              </template>

              <template v-else-if="item.status === 'inspected'">
                <template v-if="item.receiver && item.type === 'transfer'">
                  <span class="text-blue-600 italic text-xs">Awaiting recipient verification</span>
                </template>
                <template v-else>
                  <button @click="updateStatus(item.id, 'accepted')" :disabled="processing" class="text-green-600 hover:text-green-800 font-bold disabled:opacity-50 disabled:cursor-not-allowed">Accept</button>
                  <button @click="updateStatus(item.id, 'rejected')" :disabled="processing" class="text-red-600 hover:text-red-800 font-bold disabled:opacity-50 disabled:cursor-not-allowed">Reject</button>
                </template>
              </template>

              <template v-else-if="item.status === 'pending'">
                <button @click="updateStatus(item.id, 'approved')" :disabled="processing" class="text-green-600 hover:text-green-800 font-bold disabled:opacity-50 disabled:cursor-not-allowed">Approve</button>
                <button @click="updateStatus(item.id, 'rejected')" :disabled="processing" class="text-red-600 hover:text-red-800 font-bold disabled:opacity-50 disabled:cursor-not-allowed">Reject</button>
              </template>

              <template v-else-if="item.status === 'rejected'">
                <span class="text-gray-500 italic text-xs mr-2">Rejected</span>
              </template>

              <template v-else>
                <template v-if="item.status === 'pending_verification'">
                  <span class="text-blue-600 italic text-xs mr-2">Waiting for recipient</span>
                </template>
                <template v-else>
                  <span class="text-gray-400 italic text-xs mr-2">Closed</span>
                </template>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="filteredTransfers.length === 0" class="p-10 text-center text-gray-400">
        No {{ viewMode === 'pending' ? 'pending' : 'historical' }} movement requests found.
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
          <h2 class="font-bold uppercase tracking-tight text-sm">Physical Asset Inspection</h2>
          <button @click="showModal = false" class="text-white hover:text-gray-200">
            <i class="fa fa-times"></i>
          </button>
        </div>

        <div class="p-6">
          <div class="mb-4 p-3 bg-gray-50 rounded border text-xs">
            <p class="text-gray-500 font-bold uppercase">Asset Being Returned:</p>
            <p class="text-indigo-700 font-bold text-sm">{{ selectedItem.asset?.model }}</p>
            <p class="text-gray-400">Serial: {{ selectedItem.asset?.serial }} | Tag: {{ selectedItem.asset?.asset_tag }}</p>
            <p class="text-gray-400">User Report: <span class="italic">"{{ selectedItem.sender_condition || 'No notes' }}"</span></p>
          </div>
          <div v-if="selectedItem.items && selectedItem.items.length" class="mb-4 p-3 bg-gray-50 rounded border text-xs">
            <p class="text-gray-500 font-bold uppercase">Components / Accessories Included:</p>
            <ul class="list-disc list-inside mt-1 text-gray-700">
              <li v-for="itm in selectedItem.items" :key="itm.type + itm.id">
                <strong class="capitalize">{{ itm.type }}</strong>: {{ itm.name }}
                <template v-if="itm.details">
                  <div class="text-[10px] text-gray-500 ml-4">
                    <span v-if="itm.details.serial_no">Serial: {{ itm.details.serial_no }}</span>
                    <span v-if="itm.details.model_number">Model: {{ itm.details.model_number }}</span>
                    <span v-if="itm.details.category">Category: {{ itm.details.category }}</span>
                    <span v-if="itm.details.remaining_qty !== undefined">Qty left: {{ itm.details.remaining_qty }}</span>
                    <span v-if="itm.details.in_stock !== undefined">In stock: {{ itm.details.in_stock }}</span>
                  </div>
                </template>
              </li>
            </ul>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-black uppercase text-gray-500 mb-1">Official Physical Condition</label>
              <select v-model="inspectionForm.condition" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="New">New / Mint</option>
                <option value="Good">Good (Fully Functional)</option>
                <option value="Fair">Fair (Noticeable Wear)</option>
                <option value="Damaged">Damaged (Needs Repair)</option>
                <option value="Broken">Broken (Non-Functional)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-black uppercase text-gray-500 mb-1">Final Destination</label>
              <select v-model="inspectionForm.disposition" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="ready_to_deploy">Ready to Deploy</option>
                <option value="non_deployable">Non-Deployable</option>
                <option value="maintenance">Taken for Maintenance</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-black uppercase text-gray-500 mb-1">Missing Items (Admin Confirmation)</label>
              <input v-model="inspectionForm.missing_items_text" class="w-full border rounded p-2 text-sm" placeholder="e.g Charger, Dock, Mouse" />
            </div>

            <div>
              <label class="block text-xs font-black uppercase text-gray-500 mb-1">Admin Notes / Findings</label>
              <textarea v-model="inspectionForm.admin_notes" class="w-full border rounded p-2 text-sm" rows="3" placeholder="e.g Hinge broken, screen scratches, keyboard missing keys"></textarea>
            </div>

            <button @click="submitInspection" class="w-full bg-indigo-600 text-white py-2 rounded font-bold text-xs hover:bg-indigo-700">
              SAVE INSPECTION
            </button>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';
import Loader from '@/components/Loader.vue';
import eventBus from '@/eventBus';

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
// NEW: View mode state ('pending' or 'history')
const viewMode = ref('pending');

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
  if (props.mode === 'transfer') {
    return transfers.value.filter((item) => normalizeType(item.type) !== 'return');
  }
  return transfers.value;
});

// NEW: Function to handle toggle switching
const setViewMode = (mode) => {
  viewMode.value = mode;
  fetchTransfers(1);
};

const fetchTransfers = async (page = 1) => {
  loading.value = true;
  try {
    let endpoint = '/api/transfers';
    
    // UPDATED: Use the viewMode state to determine if we send ?pending=1
    if (props.mode === 'return') {
      endpoint = viewMode.value === 'pending' ? '/api/return-requests?pending=1' : '/api/return-requests';
    }
    
    if (page > 1) endpoint += (endpoint.includes('?') ? '&' : '?') + `page=${page}`;
    
    const res = await axios.get(endpoint);
    transfers.value = res.data.data || res.data;
    pagination.value = res.data;
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
    alert("Inspection recorded successfully.");
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
        // UPDATED: Only remove the item from the screen if we are looking at the 'Pending' list!
        if (props.mode === 'return' && ['closed', 'rejected'].includes(updated.status) && viewMode.value === 'pending') {
          transfers.value.splice(idx, 1);
        } else {
          transfers.value.splice(idx, 1, updated);
        }
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