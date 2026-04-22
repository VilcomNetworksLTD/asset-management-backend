<template>
  <div class="p-6 min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="max-w-5xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">System <span class="text-vilcom-blue">Settings</span></h1>
        <p class="text-slate-500 mt-1 font-medium">Manage system configurations and preferences.</p>
      </div>

      <!-- TABS -->
      <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden mb-6">
        <div class="flex border-b border-slate-100">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="currentTab = tab.id"
            class="px-6 py-4 text-sm font-bold transition-all duration-200 focus:outline-none flex items-center gap-2"
            :class="currentTab === tab.id 
              ? 'text-vilcom-blue border-b-3 border-vilcom-blue bg-blue-50/30' 
              : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          >
            <component :is="tab.icon" class="size-4" />
            {{ tab.label }}
          </button>
        </div>

        <div class="p-6">
          <!-- General Settings -->
          <div v-if="currentTab === 'general' && isAdmin" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">System Name</label>
                <div class="relative">
                  <Settings class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                  <input v-model="settings.system_name" type="text" class="w-full bg-slate-50 border-none rounded-xl p-4 pl-12 font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="e.g. Asset Tracker">
                </div>
              </div>
              
              <div>
                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Support Email</label>
                <div class="relative">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                  <input v-model="settings.support_email" type="email" class="w-full bg-slate-50 border-none rounded-xl p-4 pl-12 font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="support@example.com">
                </div>
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">System Currency</label>
              <div class="w-full bg-slate-100 rounded-xl p-4 font-bold text-slate-600 border border-slate-200">
                KSH (Kenyan Shilling)
              </div>
              <input type="hidden" v-model="settings.currency" value="KES" />
            </div>

            <div @click="settings.maintenance_mode = !settings.maintenance_mode" class="flex items-center justify-between p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all cursor-pointer border border-slate-200">
              <div class="flex items-center gap-4">
                <div class="size-12 bg-orange-50 rounded-xl flex items-center justify-center">
                  <Wrench class="size-5 text-vilcom-orange" />
                </div>
                <div>
                  <p class="font-bold text-slate-800">Maintenance Mode</p>
                  <p class="text-xs text-slate-500">Prevent users from accessing the system</p>
                </div>
              </div>
              <div :class="settings.maintenance_mode ? 'bg-vilcom-orange' : 'bg-slate-300'" class="relative w-12 h-7 rounded-full transition-all duration-300 shadow-inner">
                <div :class="settings.maintenance_mode ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow"></div>
              </div>
            </div>
          </div>

          <!-- Categories -->
          <div v-if="currentTab === 'categories' && (isAdmin || isHod)" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
            <CategorySettings />
          </div>

          <!-- Locations -->
          <div v-if="currentTab === 'locations' && (isAdmin || isHod)" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
            <LocationSettings />
          </div>

          <!-- Notifications -->
          <div v-if="currentTab === 'notifications' && isAdmin" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div class="flex items-center gap-4 p-5 bg-blue-50 rounded-xl border border-blue-100">
              <div class="size-12 bg-vilcom-blue rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/10">
                <Bell class="size-5 text-white" />
              </div>
              <div>
                <p class="font-bold text-slate-800">Notification Preferences</p>
                <p class="text-xs text-slate-500">Configure how you receive alerts</p>
              </div>
            </div>
            
            <div class="space-y-4">
              <div @click="settings.email_alerts = !settings.email_alerts" class="flex items-center justify-between p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all cursor-pointer border border-slate-200">
                <div class="flex items-center gap-4">
                  <input :checked="settings.email_alerts" type="checkbox" class="size-5 rounded border-slate-300 text-vilcom-blue focus:ring-vilcom-blue">
                  <div>
                    <p class="font-bold text-slate-800">Email Alerts</p>
                    <p class="text-xs text-slate-500">Receive emails for critical events</p>
                  </div>
                </div>
              </div>

              <div @click="settings.asset_movement_alerts = !settings.asset_movement_alerts" class="flex items-center justify-between p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all cursor-pointer border border-slate-200">
                <div class="flex items-center gap-4">
                  <input :checked="settings.asset_movement_alerts" type="checkbox" class="size-5 rounded border-slate-300 text-vilcom-blue focus:ring-vilcom-blue">
                  <div>
                    <p class="font-bold text-slate-800">Asset Movement Alerts</p>
                    <p class="text-xs text-slate-500">Notify when assets are assigned/returned</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Security -->
          <div v-if="currentTab === 'security' && isAdmin" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            <div class="flex items-center gap-4 p-5 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100">
              <div class="size-12 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <Shield class="size-5 text-white" />
              </div>
              <div>
                <p class="font-bold text-slate-800">Security Settings</p>
                <p class="text-xs text-slate-500">Configure system security options</p>
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Session Timeout (Minutes)</label>
              <div class="relative max-w-xs">
                <Clock class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                <input v-model="settings.session_timeout" type="number" class="w-full bg-slate-50 border-none rounded-xl p-4 pl-12 font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue transition-all">
              </div>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end" v-if="currentTab !== 'categories' && currentTab !== 'locations' && isAdmin">
            <button 
              @click="saveSettings" 
              :disabled="saving"
              class="bg-vilcom-blue text-white px-8 py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-xl hover:opacity-90 transition-all flex items-center gap-2 shadow-lg shadow-blue-900/10 disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <Loader2 v-if="saving" class="size-4 animate-spin" />
              <Save v-else class="size-4" />
              {{ saving ? 'Saving Changes...' : 'Save Settings' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, markRaw } from 'vue';
import axios from 'axios';
import { 
  Settings, Wrench, Mail, Bell, Shield, Clock, Save, Loader2,
  Tag, MapPin, UserCog
} from 'lucide-vue-next';
import CategorySettings from './CategorySettings.vue';
import LocationSettings from './LocationSettings.vue';

const user = JSON.parse(localStorage.getItem('user_data') || '{}');
const role = (user.role || '').toLowerCase();
const isAdmin = role === 'admin';
const isHod = role === 'head_of_department' || role === 'hod' || role === 'manager' || role === 'management';

const allTabs = [
  { id: 'general', label: 'General', icon: markRaw(Settings) },
  { id: 'categories', label: 'Categories', icon: markRaw(Tag) },
  { id: 'locations', label: 'Locations', icon: markRaw(MapPin) },
  { id: 'notifications', label: 'Notifications', icon: markRaw(Bell) },
  { id: 'security', label: 'Security', icon: markRaw(Shield) }
];

const tabs = computed(() => {
  if (isAdmin) return allTabs;
  if (isHod) return allTabs.filter(t => ['categories', 'locations'].includes(t.id));
  return [];
});

const currentTab = ref(isAdmin ? 'general' : 'categories');
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
  if (!isAdmin) return;
  try {
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
