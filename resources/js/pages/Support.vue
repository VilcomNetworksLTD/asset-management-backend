<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">IT Support & Help Desk</h1>

      <!-- ADMIN VIEW: List of Tickets -->
      <div v-if="isAdmin" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
          <h2 class="font-bold text-gray-700">Support Tickets</h2>
          <button @click="fetchTickets" class="text-sm text-blue-600 hover:underline">Refresh List</button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Requester</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Subject / Issue</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Priority</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="ticket in tickets" :key="ticket.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ ticket.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ ticket.user?.name || 'Unknown' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  <div class="max-w-xs truncate" :title="ticket.Description">
                    {{ ticket.Description.split('\n')[0] }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="{
                    'text-red-600 font-bold': ticket.Priority === 'high',
                    'text-yellow-600 font-bold': ticket.Priority === 'medium',
                    'text-green-600 font-bold': ticket.Priority === 'low'
                  }">{{ ticket.Priority ? ticket.Priority.toUpperCase() : 'NORMAL' }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ ticket.status?.Status_Name || 'Pending' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button 
                    @click="openResolveModal(ticket)"
                    class="text-indigo-600 hover:text-indigo-900 font-bold"
                  >
                    Resolve
                  </button>
                </td>
              </tr>
              <tr v-if="tickets.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No tickets found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- STAFF VIEW: Report Form -->
      <div v-else class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
        <h2 class="text-lg font-semibold mb-4">Report an Issue</h2>
        <p class="text-gray-600 mb-6 text-sm">
          Use this form for general IT issues (e.g., Email problems, Network connectivity, Software glitches) that aren't tied to a specific physical asset.
        </p>

        <form @submit.prevent="submitTicket" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Subject</label>
            <input 
              v-model="form.subject" 
              type="text" 
              placeholder="e.g. Outlook not syncing"
              class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Priority</label>
            <select 
              v-model="form.priority" 
              class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Description</label>
            <textarea 
              v-model="form.description" 
              rows="5" 
              placeholder="Please describe the issue in detail..."
              class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none"
              required
            ></textarea>
          </div>

          <div class="flex justify-end">
            <button 
              type="submit" 
              :disabled="loading"
              class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 disabled:opacity-50"
            >
              {{ loading ? 'Submitting...' : 'Submit Ticket' }}
            </button>
          </div>
        </form>
      </div>

      <!-- RESOLVE MODAL -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-bold mb-4">Resolve Ticket #{{ selectedTicket?.id }}</h3>
          
          <!-- new status no longer selected manually; resolution action will set it automatically -->

          <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Resolution / Comments</label>
            <textarea 
              v-model="resolveForm.communication" 
              rows="4" 
              class="w-full border rounded p-2"
              placeholder="Describe how the issue was resolved..."
            ></textarea>
          </div>

          <div class="flex justify-end space-x-2">
            <button @click="showModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
            <button @click="submitResolution" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Resolve Ticket</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isAdmin = ref(false);
const loading = ref(false);
const tickets = ref([]);
const statuses = ref([]);
const showModal = ref(false);
const selectedTicket = ref(null);

// Form for Staff
const form = ref({
  subject: '',
  priority: 'medium',
  description: ''
});

// Form for Admin Resolution (status handled automatically)
const resolveForm = ref({
  communication: ''
});

onMounted(async () => {
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  isAdmin.value = (userData.role === 'admin');

  if (isAdmin.value) {
    await fetchTickets();
    await fetchStatuses();
  }
});

const fetchTickets = async () => {
  try {
    const res = await axios.get('/api/tickets');
    tickets.value = res.data || [];
  } catch (error) {
    console.error('Error fetching tickets:', error);
  }
};

const fetchStatuses = async () => {
  try {
    const res = await axios.get('/api/statuses');
    statuses.value = res.data || [];
  } catch (error) {
    console.error('Error fetching statuses:', error);
  }
};

const submitTicket = async () => {
  loading.value = true;
  try {
    // This hits the TicketController@store endpoint we modified earlier
    await axios.post('/api/tickets', form.value);
    alert('Support ticket created successfully. Our IT team will review it shortly.');
    form.value = { subject: '', priority: 'medium', description: '' };
  } catch (error) {
    console.error(error);
    alert(error.response?.data?.message || 'Failed to submit ticket. Please try again.');
  } finally {
    loading.value = false;
  }
};

const openResolveModal = (ticket) => {
  selectedTicket.value = ticket;
  resolveForm.value.communication = '';
  showModal.value = true;
};

const submitResolution = async () => {
  if (!selectedTicket.value) return;
  
  try {
    await axios.put(`/api/tickets/${selectedTicket.value.id}`, {
      action: 'resolve',
      communication: resolveForm.value.communication
    });
    alert('Ticket updated successfully.');
    showModal.value = false;
    fetchTickets(); // Refresh list
    eventBus.emit('ticket-changed');
  } catch (error) {
    console.error(error);
    alert('Failed to update ticket.');
  }
};
</script>