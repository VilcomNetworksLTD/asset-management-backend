<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">{{ headingText }}</h1>

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
          <tr v-for="item in filteredTransfers" :key="item.id" class="hover:bg-gray-50">
            <td class="p-4 font-medium">
              {{ item.asset?.model || 'Unknown Asset' }}
              <div class="text-[10px] text-gray-400 font-mono">{{ item.asset?.serial }}</div>
            </td>
            <td class="p-4">
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                {{ item.type || 'Transfer' }}
              </span>
            </td>
            <td class="p-4">{{ item.sender?.name }}</td>
            <td v-if="!isReturnMode" class="p-4">{{ item.receiver?.name || 'Admin / Office' }}</td>
            <td v-else class="p-4">
              <div class="text-xs text-gray-700">
                <strong>Condition:</strong> {{ item.sender_condition || 'N/A' }}
              </div>
              <div class="text-xs text-gray-500">
                <strong>Missing:</strong>
                {{ Array.isArray(item.missing_items) && item.missing_items.length ? item.missing_items.join(', ') : 'None' }}
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
                <button @click="openInspectionModal(item)" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700 shadow-sm">
                  INSPECT
                </button>
              </template>

              <template v-else-if="item.status === 'inspected'">
                <button @click="updateStatus(item.id, 'accepted')" class="text-green-600 hover:text-green-800 font-bold">Accept</button>
                <button @click="updateStatus(item.id, 'rejected')" class="text-red-600 hover:text-red-800 font-bold">Reject</button>
              </template>

              <template v-else-if="item.status === 'pending'">
                <button @click="updateStatus(item.id, 'approved')" class="text-green-600 hover:text-green-800 font-bold">Approve</button>
                <button @click="updateStatus(item.id, 'rejected')" class="text-red-600 hover:text-red-800 font-bold">Reject</button>
              </template>

              <template v-else>
                <span class="text-gray-400 italic text-xs mr-2">Closed</span>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="filteredTransfers.length === 0" class="p-10 text-center text-gray-400">
        No movement requests found.
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
            <p class="text-gray-400">User Report: <span class="italic">"{{ selectedItem.sender_condition || 'No notes' }}"</span></p>
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
  if (props.mode === 'transfer') {
    return transfers.value.filter((item) => normalizeType(item.type) !== 'return');
  }

  if (props.mode === 'return') {
    return transfers.value;
  }

  return transfers.value;
});

const fetchTransfers = async () => {
  const endpoint = props.mode === 'return' ? '/api/return-requests' : '/api/transfers';
  const res = await axios.get(endpoint);
  transfers.value = res.data;
};

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
  try {
    const endpoint = props.mode === 'return'
      ? `/api/admin/return-requests/${selectedItem.value.id}/complete`
      : `/api/admin/transfers/${selectedItem.value.id}/complete`;

    await axios.post(endpoint, {
      condition: inspectionForm.value.condition,
      disposition: inspectionForm.value.disposition,
      missing_items: (inspectionForm.value.missing_items_text || '')
        .split(',')
        .map(v => v.trim())
        .filter(Boolean),
      admin_notes: inspectionForm.value.admin_notes || null,
    });
    showModal.value = false;
    alert("Inspection recorded successfully.");
    fetchTransfers();
  } catch (err) {
    alert("Error saving inspection. Check backend logs.");
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
  try {
    const endpoint = props.mode === 'return' ? `/api/return-requests/${id}/status` : `/api/transfers/${id}/status`;
    await axios.put(endpoint, { status });
    fetchTransfers();
  } catch (err) {
    alert("Update failed.");
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
    'lost': 'bg-gray-800 text-white',
  };
  return classes[status] || 'bg-gray-100 text-gray-600';
};

onMounted(fetchTransfers);
</script>