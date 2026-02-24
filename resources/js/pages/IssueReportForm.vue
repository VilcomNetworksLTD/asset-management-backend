<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      <div class="bg-[#3c8dbc] p-4 text-white font-bold text-lg">
        <i class="fa fa-laptop mr-2"></i> Request Equipment / Report Issue
      </div>
      <form @submit.prevent="submitTicket" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Request Type</label>
          <div class="flex gap-4 mt-2 text-sm">
            <label class="flex items-center gap-2">
              <input type="radio" value="equipment_request" v-model="form.type" />
              Request Equipment (General)
            </label>
            <label class="flex items-center gap-2">
              <input type="radio" value="issue_report" v-model="form.type" />
              Report Issue (Specific Asset)
            </label>
          </div>
        </div>

        <div v-if="form.type === 'equipment_request'">
          <label class="block text-sm font-bold text-gray-700 mb-1">Equipment Category</label>
          <select v-model="form.requested_category" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
            <option value="" disabled>-- What do you need? --</option>
            <option value="Laptop">Laptop</option>
            <option value="Desktop">Desktop</option>
            <option value="Monitor">Monitor</option>
            <option value="Printer">Printer</option>
            <option value="Accessory">Accessory</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Priority</label>
          <select v-model="form.priority" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        <div v-if="form.type === 'issue_report'">
          <label class="block text-sm font-bold text-gray-700 mb-1">Select Affected Equipment</label>
          <select v-model="form.asset_id" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
            <option value="" disabled>-- Which device has the issue? --</option>
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.model }} ({{ asset.serial }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">Description</label>
          <textarea v-model="form.description" rows="4" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none" :placeholder="form.type === 'equipment_request' ? 'Describe what you need and why...' : 'Describe what is wrong...'" required></textarea>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" @click="$router.push('/dashboard/user')" class="px-4 py-2 text-gray-500">Cancel</button>
          <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition">
            {{ form.type === 'equipment_request' ? 'Submit Request' : 'Submit Ticket' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const myAssets = ref([]);
const form = ref({
  type: 'equipment_request',
  requested_category: '',
  asset_id: '',
  description: '',
  priority: 'medium'
});

onMounted(async () => {
  const res = await axios.get('/api/user-stats');
  myAssets.value = res.data.recent_assets;
});

const submitTicket = async () => {
  const payload = {
    description: form.value.description,
    priority: form.value.priority
  };

  if (form.value.type === 'equipment_request') {
    payload.requested_category = form.value.requested_category;
  } else {
    payload.asset_id = form.value.asset_id;
  }

  await axios.post('/api/tickets', payload);
  alert(form.value.type === 'equipment_request' ? 'Request submitted successfully!' : 'Ticket submitted successfully!');
  router.push('/dashboard/user/my-tickets');
};
</script>