<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-indigo-700 p-4 text-white font-bold text-lg">
        <i class="fa fa-undo mr-2"></i> Initiate Asset Return
      </div>
      <form @submit.prevent="submitReturn" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-bold text-gray-700">Which asset are you returning?</label>
          <select v-model="form.asset_id" class="w-full border rounded p-2 mt-1" required>
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.model }} ({{ asset.serial }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Current Condition (Self-Assessment)</label>
          <select v-model="form.sender_condition" class="w-full border rounded p-2 mt-1" required>
            <option value="good">Working / Good</option>
            <option value="damaged">Minor Damage (Scratches/Dents)</option>
            <option value="broken">Broken / Non-Functional</option>
            <option value="lost">Lost / Stolen</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Any missing peripherals?</label>
          <textarea v-model="form.notes" class="w-full border rounded p-2 mt-1" placeholder="e.g. Returned without charger..."></textarea>
        </div>

        <div class="bg-blue-50 p-4 rounded text-blue-700 text-sm">
          <strong>Note:</strong> Your responsibility for this asset only ends once the Admin physically inspects and accepts it.
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700">
          Submit Return Request
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const myAssets = ref([]);
const router = useRouter();
const form = ref({ asset_id: '', sender_condition: 'good', notes: '' });

onMounted(async () => {
  const res = await axios.get('/api/my-returnable-assets');
  myAssets.value = res.data || [];
});

const submitReturn = async () => {
  await axios.post('/api/return-requests', form.value);
  alert('Return initiated! Please hand over the device to the Admin office.');
  router.push('/dashboard/user');
};
</script>