<template>
  <div class="p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      <div class="bg-orange-500 p-4 text-white font-bold">
        Request Recall / Reassign
      </div>
      <div class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-bold text-gray-700">Select Asset</label>
          <select v-model="form.asset_id" class="w-full border rounded p-2 mt-1">
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.model }} ({{ asset.serial }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Movement Type</label>
          <div class="flex gap-4 mt-2">
            <label class="flex items-center gap-2">
              <input type="radio" v-model="form.type" value="return"> Direct Return to Office
            </label>
            <label class="flex items-center gap-2">
              <input type="radio" v-model="form.type" value="transfer"> Transfer to Colleague
            </label>
          </div>
        </div>

        <div v-if="form.type === 'transfer'">
          <label class="block text-sm font-bold text-gray-700">Transfer To (Employee)</label>
          <select v-model="form.receiver_id" class="w-full border rounded p-2 mt-1">
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Self-Reported Condition</label>
          <select v-model="form.sender_condition" class="w-full border rounded p-2 mt-1">
            <option value="good">Good / Working</option>
            <option value="damaged">Damaged / Needs Repair</option>
            <option value="lost">Lost / Stolen</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Missing Items (comma separated)</label>
          <input v-model="form.missing_items_text" class="w-full border rounded p-2 mt-1" placeholder="e.g Charger, Mouse dongle" />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Issues Observed</label>
          <textarea v-model="form.issue_notes" class="w-full border rounded p-2 mt-1" rows="3" placeholder="e.g Battery drains fast, cracked hinge"></textarea>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Additional Notes</label>
          <textarea v-model="form.notes" class="w-full border rounded p-2 mt-1" rows="2" placeholder="Any extra handover details"></textarea>
        </div>

        <button @click="submitRequest" class="w-full bg-orange-600 text-white font-bold py-2 rounded mt-4">
          Send Request
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const myAssets = ref([]);
const users = ref([]);
const form = ref({
  asset_id: '',
  type: 'return',
  receiver_id: '',
  sender_condition: 'good',
  missing_items_text: '',
  issue_notes: '',
  notes: '',
});

onMounted(async () => {
  const [assetRes, userRes] = await Promise.all([
    axios.get('/api/my-assets'),
    axios.get('/api/users-list')
  ]);
  myAssets.value = assetRes.data || [];
  users.value = userRes.data;
});

const submitRequest = async () => {
  const payload = {
    asset_id: form.value.asset_id,
    type: form.value.type,
    receiver_id: form.value.type === 'transfer' ? form.value.receiver_id : null,
    sender_condition: form.value.sender_condition,
    missing_items: (form.value.missing_items_text || '')
      .split(',')
      .map(v => v.trim())
      .filter(Boolean),
    issue_notes: form.value.issue_notes || null,
    notes: form.value.notes || null,
  }

  await axios.post('/api/transfers/return', payload);
  alert("Request sent! Please bring the asset to the Admin for physical inspection.");
  form.value = {
    asset_id: '',
    type: 'return',
    receiver_id: '',
    sender_condition: 'good',
    missing_items_text: '',
    issue_notes: '',
    notes: '',
  }
};
</script>