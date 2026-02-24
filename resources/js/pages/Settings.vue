<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Global Settings</h1>
      <p class="text-sm text-gray-500">Configure system-wide preferences and branding.</p>
    </div>

    <div class="bg-white rounded shadow-sm border border-gray-200 flex flex-col md:flex-row min-h-[500px]">
      <div class="w-full md:w-64 border-r border-gray-100 bg-gray-50/50 p-4 space-y-1">
        <button 
          @click="activeTab = 'general'"
          :class="activeTab === 'general' ? 'bg-[#3c8dbc] text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'"
          class="w-full text-left px-4 py-2.5 rounded text-sm font-medium transition-all"
        >
          <i class="fa fa-cog mr-2"></i> General Settings
        </button>
        <button 
          @click="activeTab = 'localization'"
          :class="activeTab === 'localization' ? 'bg-[#3c8dbc] text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'"
          class="w-full text-left px-4 py-2.5 rounded text-sm font-medium transition-all"
        >
          <i class="fa fa-globe mr-2"></i> Localization
        </button>
        <button 
          @click="activeTab = 'operations'"
          :class="activeTab === 'operations' ? 'bg-[#3c8dbc] text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'"
          class="w-full text-left px-4 py-2.5 rounded text-sm font-medium transition-all"
        >
          <i class="fa fa-sliders-h mr-2"></i> Operations
        </button>
      </div>

      <div class="flex-1 p-8">
        <div v-if="loading" class="text-center py-10 text-gray-500">
          <i class="fa fa-spinner fa-spin mr-2"></i> Loading settings...
        </div>

        <form v-else @submit.prevent="saveSettings">
          <div v-if="message" class="mb-4 px-4 py-2 rounded text-sm" :class="messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
            {{ message }}
          </div>

          <div v-if="activeTab === 'general'" class="space-y-6 max-w-2xl">
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Site Name</label>
              <input v-model="form.site_name" type="text" class="w-full border rounded p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Company Name</label>
              <input v-model="form.company_name" type="text" class="w-full border rounded p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Admin Email</label>
              <input v-model="form.admin_email" type="email" class="w-full border rounded p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Support Email</label>
              <input v-model="form.support_email" type="email" class="w-full border rounded p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
          </div>

          <div v-if="activeTab === 'localization'" class="space-y-6 max-w-2xl">
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Currency Symbol</label>
              <input v-model="form.currency" type="text" class="w-full border rounded p-2.5 text-sm" placeholder="e.g. $">
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Date Format</label>
              <select v-model="form.date_format" class="w-full border rounded p-2.5 text-sm">
                <option value="Y-m-d">YYYY-MM-DD</option>
                <option value="d/m/Y">DD/MM/YYYY</option>
                <option value="m/d/Y">MM/DD/YYYY</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Timezone</label>
              <select v-model="form.timezone" class="w-full border rounded p-2.5 text-sm">
                <option value="Africa/Nairobi">Africa/Nairobi</option>
                <option value="UTC">UTC</option>
                <option value="Europe/London">Europe/London</option>
                <option value="America/New_York">America/New_York</option>
              </select>
            </div>
          </div>

          <div v-if="activeTab === 'operations'" class="space-y-6 max-w-2xl">
            <div>
              <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Low Stock Threshold</label>
              <input v-model.number="form.low_stock_threshold" type="number" min="0" class="w-full border rounded p-2.5 text-sm" />
              <p class="text-xs text-gray-500 mt-1">Used to flag low quantities in inventory pages.</p>
            </div>
          </div>

          <div class="mt-10 pt-6 border-t">
            <button 
              type="submit" 
              :disabled="saving"
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded text-sm font-bold transition-colors disabled:opacity-50"
            >
              <i class="fa fa-save mr-2"></i> {{ saving ? 'Saving...' : 'Save Changes' }}
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

const activeTab = ref('general');
const loading = ref(true);
const saving = ref(false);
const message = ref('');
const messageType = ref('success');
const form = ref({
  site_name: '',
  company_name: '',
  admin_email: '',
  support_email: '',
  currency: '$',
  date_format: 'Y-m-d',
  timezone: 'Africa/Nairobi',
  low_stock_threshold: 5,
});

const fetchSettings = async () => {
  try {
    const res = await axios.get('/api/settings');
    Object.assign(form.value, res.data);
  } catch (err) {
    console.error("Failed to load settings:", err);
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  message.value = '';
  try {
    const res = await axios.post('/api/settings', form.value);
    Object.assign(form.value, res.data?.settings || {});
    messageType.value = 'success';
    message.value = 'Settings saved successfully.';
  } catch (err) {
    messageType.value = 'error';
    message.value = err.response?.data?.message || 'Error saving settings.';
  } finally {
    saving.value = false;
  }
};

onMounted(fetchSettings);
</script>