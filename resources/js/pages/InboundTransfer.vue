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
              <p class="text-gray-500 text-sm" v-if="assignment.asset?.status_name">Status: {{ assignment.asset.status_name }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-400 uppercase font-bold">Issued By</p>
              <p class="text-sm font-medium">{{ assignment.admin?.name || 'System Admin' }}</p>
            </div>
          </div>
          <div v-if="assignment.items && assignment.items.length" class="mb-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-xs font-bold uppercase text-gray-500">Actual Items in the transfer</p>
            <ul class="list-disc list-inside text-sm text-gray-700 mt-2">
              <li v-for="itm in assignment.items" :key="itm.type + itm.id">
                <strong class="capitalize">{{ itm.type }}</strong>: {{ itm.name }}
                <div v-if="itm.details" class="text-[10px] text-gray-500 ml-4">
                  <span v-if="itm.details.serial_no">Serial: {{ itm.details.serial_no }}</span>
                  <span v-if="itm.details.model_number">Model: {{ itm.details.model_number }}</span>
                  <span v-if="itm.details.category">Category: {{ itm.details.category }}</span>
                  <span v-if="itm.details.remaining_qty !== undefined">Qty left: {{ itm.details.remaining_qty }}</span>
                  <span v-if="itm.details.in_stock !== undefined">In stock: {{ itm.details.in_stock }}</span>
                </div>
              </li>
            </ul>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg mb-6">
            <div>
              <p class="text-xs text-gray-400 uppercase font-bold">Admin Recorded Condition</p>
              <p class="text-sm font-bold text-gray-700">{{ assignment.admin_condition || 'Good' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 uppercase font-bold">Included Peripherals</p>
              <div class="text-sm text-gray-700">
                <ul class="list-disc list-inside">
                  <li v-for="itm in assignment.included_items" :key="itm">{{ itm }}</li>
                </ul>
              </div>
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
import eventBus from '@/eventBus';

const pendingAssignments = ref([]);

const fetchPending = async () => {
  try {
    const res = await axios.get('/api/my-pending-assignments');
    pendingAssignments.value = res.data;
  } catch (err) {
    console.error("Error fetching assignments", err);
  }
};

onMounted(() => {
  fetchPending();
  eventBus.on('transfer-changed', fetchPending);
});

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