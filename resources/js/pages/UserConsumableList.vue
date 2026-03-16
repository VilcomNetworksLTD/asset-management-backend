<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-2xl font-semibold text-gray-800 mb-4">My Consumables</h1>
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
              <tr class="text-[11px] uppercase text-gray-500 font-bold">
                <th class="p-4">Item Name</th>
                <th class="p-4">Category</th>
                <th class="p-4">Quantity</th>
                <th class="p-4">Checkout Date</th>
              </tr>
            </thead>
            <tbody class="divide-y text-sm">
              <tr v-if="loading">
                <td colspan="4" class="p-8 text-center"><Loader /></td>
              </tr>
              <tr v-else-if="consumables.length === 0">
                <td colspan="4" class="p-8 text-center text-gray-400">No consumables assigned to you.</td>
              </tr>
              <tr v-for="consumable in consumables" :key="consumable.id" class="hover:bg-gray-50">
                <td class="p-4 font-medium text-gray-800">{{ consumable.item_name }}</td>
                <td class="p-4 text-gray-600">{{ consumable.category || 'N/A' }}</td>
                <td class="p-4 text-gray-600">{{ consumable.pivot.quantity }}</td>
                <td class="p-4 text-gray-600">{{ formatDate(consumable.pivot.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const consumables = ref([]);
const loading = ref(true);

const fetchMyConsumables = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/my-consumables');
    consumables.value = data;
  } catch (error) {
    console.error("Failed to fetch user's consumables:", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

onMounted(fetchMyConsumables);
</script>
