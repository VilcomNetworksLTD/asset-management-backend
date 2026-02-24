<template>
  <div class="p-6 bg-white border rounded-xl shadow-lg">
    <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-800">Admin Inspection Form</h2>
    
    <div class="grid grid-cols-2 gap-6 mb-6">
      <div class="p-3 bg-gray-50 rounded">
        <p class="text-xs text-gray-400 uppercase">Reported by Staff</p>
        <p class="font-bold">{{ pendingTransfer.sender_condition }}</p>
      </div>
      <div class="p-3 bg-gray-50 rounded">
        <p class="text-xs text-gray-400 uppercase">Staff Notes</p>
        <p class="font-bold">{{ pendingTransfer.notes || 'None' }}</p>
      </div>
    </div>

    <div class="space-y-4">
      <div>
        <label class="block font-bold text-gray-700">Official Physical State</label>
        <div class="flex gap-2 mt-1">
          <button v-for="state in ['New', 'Good', 'Fair', 'Damaged', 'Broken']" 
            :key="state" @click="adminForm.condition = state"
            :class="adminForm.condition === state ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
            class="px-3 py-1 rounded-full text-sm font-semibold transition">
            {{ state }}
          </button>
        </div>
      </div>

      <div>
        <label class="block font-bold text-gray-700">Peripheral Audit (Missing?)</label>
        <div class="grid grid-cols-2 gap-2 mt-2">
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="adminForm.missing_items" value="charger"> <span>Charger</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="adminForm.missing_items" value="bag"> <span>Laptop Bag</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="adminForm.missing_items" value="mouse"> <span>Mouse/Keyboard</span>
          </label>
        </div>
      </div>

      <div class="pt-4 flex gap-3">
        <button @click="processReturn('Ready to Deploy')" class="flex-1 bg-green-600 text-white py-2 rounded font-bold">
          Accept & Restock
        </button>
        <button @click="processReturn('Out for Repair')" class="flex-1 bg-orange-500 text-white py-2 rounded font-bold">
          Send to Maintenance
        </button>
        <button @click="processReturn('Archived/Lost')" class="flex-1 bg-red-600 text-white py-2 rounded font-bold">
          Mark as Lost
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps(['pendingTransfer']);
const adminForm = ref({ condition: 'Good', missing_items: [] });

const processReturn = async (nextStatus) => {
  await axios.post(`/api/admin/transfers/${props.pendingTransfer.id}/complete`, {
    ...adminForm.value,
    asset_status: nextStatus
  });
  alert('Inspection Complete. Asset status updated.');
  emit('refresh');
};
</script>