<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">System Feedback</h1>

      <!-- ADMIN VIEW: List of Feedback -->
      <div v-if="isAdmin" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b bg-gray-50">
          <h2 class="font-bold text-gray-700">User Feedback Received</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Asset</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Comments</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in feedbackList" :key="item.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(item.created_at).toLocaleDateString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                {{ item.employee?.name || 'Unknown' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span v-if="item.asset" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded font-bold">
                  {{ item.asset.Asset_Name }}
                </span>
                <span v-else class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded font-bold">
                  General
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">
                {{ item.Comments }}
              </td>
            </tr>
            <tr v-if="feedbackList.length === 0">
              <td colspan="4" class="px-6 py-4 text-center text-gray-500">No feedback found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- STAFF VIEW: Submit Feedback -->
      <div v-else class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
        <h2 class="text-lg font-bold mb-4">Send Feedback to Admin</h2>
        <p class="text-gray-600 mb-4 text-sm">
          Have a suggestion or found a bug? Let us know!
        </p>
        <form @submit.prevent="submitFeedback">
          <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Comments</label>
            <textarea 
              v-model="form.Comments" 
              rows="5" 
              class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-green-500 outline-none"
              placeholder="Your feedback here..."
              required
            ></textarea>
          </div>
          <div class="flex justify-end">
            <button 
              type="submit" 
              :disabled="loading"
              class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700 disabled:opacity-50"
            >
              {{ loading ? 'Sending...' : 'Send Feedback' }}
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isAdmin = ref(false);
const loading = ref(false);
const feedbackList = ref([]);
const form = ref({ Comments: '' });

onMounted(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  isAdmin.value = (userData.role === 'admin');

  if (isAdmin.value) {
    fetchFeedback();
  }
});

const fetchFeedback = async () => {
  try {
    const res = await axios.get('/api/feedback');
    feedbackList.value = res.data.data || [];
  } catch (error) {
    console.error('Error fetching feedback:', error);
  }
};

const submitFeedback = async () => {
  loading.value = true;
  try {
    // Ensure Asset_ID is explicitly null if not provided
    const payload = { ...form.value, Asset_ID: null };
    await axios.post('/api/feedback', payload);
    alert('Thank you for your feedback!');
    form.value.Comments = '';
  } catch (error) {
    console.error(error);
    const msg = error.response?.data?.message || 'Failed to send feedback.';
    alert('Error: ' + msg);
  } finally {
    loading.value = false;
  }
};
</script>
