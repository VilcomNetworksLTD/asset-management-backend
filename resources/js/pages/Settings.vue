<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-500 mt-1">Manage system configurations and preferences.</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex border-b border-gray-200">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="currentTab = tab.id"
            class="px-6 py-4 text-sm font-medium transition-colors duration-200 focus:outline-none flex items-center"
            :class="currentTab === tab.id ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
          >
            <i :class="['fa mr-2', tab.icon]"></i>
            {{ tab.label }}
          </button>
        </div>

        <div class="p-6">
          <!-- General Settings -->
          <div v-if="currentTab === 'general'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">System Name</label>
              <input v-model="settings.system_name" type="text" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200 p-2.5 border text-sm" placeholder="e.g. Asset Tracker">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Support Email</label>
              <input v-model="settings.support_email" type="email" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200 p-2.5 border text-sm" placeholder="support@example.com">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">System Currency</label>
              <select v-model="settings.currency" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200 p-2.5 border text-sm">
                <option value="KES">Kenyan Shilling (KES)</option>
                <option value="USD">US Dollar (USD)</option>
                <option value="EUR">Euro (EUR)</option>
                <option value="GBP">British Pound (GBP)</option>
              </select>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
              <div>
                <h4 class="font-bold text-gray-800 text-sm">Maintenance Mode</h4>
                <p class="text-xs text-gray-500">Prevent users from accessing the system.</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.maintenance_mode" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
          </div>

          <!-- Notifications -->
          <div v-if="currentTab === 'notifications'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div class="space-y-4">
              <div class="flex items-start">
                <div class="flex items-center h-5">
                  <input id="email_alerts" v-model="settings.email_alerts" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                  <label for="email_alerts" class="font-bold text-gray-700">Email Alerts</label>
                  <p class="text-gray-500">Receive emails for critical system events.</p>
                </div>
              </div>

              <div class="flex items-start">
                <div class="flex items-center h-5">
                  <input id="asset_movement" v-model="settings.asset_movement_alerts" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                  <label for="asset_movement" class="font-bold text-gray-700">Asset Movement</label>
                  <p class="text-gray-500">Notify admins when assets are assigned or returned.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Security -->
          <div v-if="currentTab === 'security'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
             <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Session Timeout (Minutes)</label>
              <input v-model="settings.session_timeout" type="number" class="w-full md:w-1/3 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200 p-2.5 border text-sm">
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button 
              @click="saveSettings" 
              :disabled="saving"
              class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-blue-700 transition-all shadow-md hover:shadow-lg flex items-center disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <i v-if="saving" class="fa fa-spinner fa-spin mr-2"></i>
              {{ saving ? 'Saving Changes...' : 'Save Settings' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const tabs = [
  { id: 'general', label: 'General', icon: 'fa-cog' },
  { id: 'notifications', label: 'Notifications', icon: 'fa-bell' },
  { id: 'security', label: 'Security', icon: 'fa-lock' }
];

const currentTab = ref('general');
const saving = ref(false);
const settings = ref({
  system_name: '',
  support_email: '',
  currency: 'KES',
  maintenance_mode: false,
  email_alerts: true,
  asset_movement_alerts: true,
  session_timeout: 60
});

const fetchSettings = async () => {
  try {
    // the public endpoint returns the same data; there is no need for an
    // additional /admin prefix here.  the composable also uses /api/settings.
    const response = await axios.get('/api/settings');
    settings.value = { ...settings.value, ...response.data };
  } catch (error) {
    console.error('Failed to fetch settings:', error);
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const resp = await axios.post('/api/settings', settings.value);
    // the API echoes back the stored values; merge them so any defaults
    // applied server‑side (or the newly‑saved currency) propagate to every
    // component that uses the shared settings ref.
    if (resp.data && resp.data.settings) {
      settings.value = { ...settings.value, ...resp.data.settings };
    }
  } catch (error) {
    console.error('Failed to save settings:', error);
  } finally {
    saving.value = false;
  }
};

onMounted(fetchSettings);
</script>