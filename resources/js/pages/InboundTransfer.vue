<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Pending Assignments</h2>
      
      <div v-if="pendingAssignments.length === 0" class="bg-white p-10 rounded-xl text-center shadow-sm">
        <i class="fa fa-clipboard-check text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">No new assets are currently awaiting your verification.</p>
      </div>

      <div v-for="assignment in pendingAssignments" :key="assignment.id" class="bg-white rounded-xl shadow-md overflow-hidden mb-6 border-t-4 border-green-500">
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <div>
              <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded uppercase">New Assignment</span>
              <h3 class="text-xl font-bold text-gray-900 mt-2">{{ assignment.asset?.model }}</h3>
              <p class="text-gray-500 text-sm">Serial: {{ assignment.asset?.serial }} | Tag: {{ assignment.asset?.asset_tag }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-400 uppercase font-bold">Issued By</p>
              <p class="text-sm font-medium">{{ assignment.admin?.name || 'System Admin' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg mb-6">
            <div>
              <p class="text-xs text-gray-400 uppercase font-bold">Admin Recorded Condition</p>
              <p class="text-sm font-bold text-gray-700">{{ assignment.admin_condition || 'Good' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 uppercase font-bold">Included Peripherals</p>
              <p class="text-sm text-gray-700">{{ formatPeripherals(assignment.included_items) }}</p>
            </div>
          </div>

          <div class="border-t pt-6">
            <p class="text-sm text-gray-600 mb-4">
              By clicking <strong>"Accept & Verify"</strong>, you confirm that you have physically received this asset in the condition stated above.
            </p>
            
            <div class="flex flex-col md:flex-row gap-3">
              <button @click="confirmAssignment(assignment.id, 'accepted')" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                <i class="fa fa-check-circle mr-2"></i> Accept & Verify
              </button>
              <button @click="confirmAssignment(assignment.id, 'disputed')" class="flex-1 bg-white border border-red-300 text-red-600 py-3 rounded-lg font-bold hover:bg-red-50 transition">
                <i class="fa fa-times-circle mr-2"></i> Report Discrepancy
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const pendingAssignments = ref([]);

const fetchPending = async () => {
  try {
    const res = await axios.get('/api/my-pending-assignments');
    pendingAssignments.value = res.data;
  } catch (err) {
    console.error("Error fetching assignments", err);
  }
};

onMounted(fetchPending);

const formatPeripherals = (items) => {
  if (!items || items.length === 0) return 'Standard Set (Charger included)';
  return Array.isArray(items) ? items.join(', ') : items;
};

const confirmAssignment = async (id, status) => {
  try {
    await axios.post(`/api/assignments/${id}/verify`, { status });
    alert(status === 'accepted' ? 'Asset verified and added to your inventory.' : 'Discrepancy reported to Admin.');
    fetchPending();
  } catch (err) {
    alert('Failed to process verification.');
  }
};
</script>