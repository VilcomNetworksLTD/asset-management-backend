<template>
  <div class="p-6 min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="max-w-5xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Account <span class="text-vilcom-blue">Settings</span></h1>
        <p class="text-slate-500 mt-1 font-medium">Manage your profile, preferences, and security.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- ACCOUNT INFORMATION -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden group hover:shadow-2xl transition-all duration-300">
          <div class="p-5 border-b bg-gradient-to-r from-vilcom-blue to-blue-700 flex items-center gap-3">
            <div class="p-2 bg-white/20 rounded-xl">
              <User class="size-5 text-white" />
            </div>
            <h3 class="font-bold text-white text-lg">Account Information</h3>
          </div>
          <div class="p-6 space-y-5">
            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl">
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(profileForm.name || 'User')}&background=1e40af&color=fff`" class="size-14 rounded-2xl border-2 border-vilcom-blue shadow-lg" alt="Avatar">
              <div>
                <p class="font-bold text-slate-800 text-lg">{{ profileForm.name || 'Your Name' }}</p>
                <p class="text-sm text-slate-500">{{ profileForm.email || 'your@email.com' }}</p>
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Full Name</label>
              <div class="relative">
                <User class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                <input v-model="profileForm.name" type="text" class="w-full bg-slate-50 border-none rounded-xl p-4 pl-12 font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="Enter your full name">
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Email Address</label>
              <div class="relative">
                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                <input v-model="profileForm.email" type="email" class="w-full bg-slate-50 border-none rounded-xl p-4 pl-12 font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="Enter your email">
              </div>
            </div>
            
            <button @click="updateProfile" :disabled="loading.profile" class="w-full bg-vilcom-blue text-white px-6 py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-900/10">
              <Loader2 v-if="loading.profile" class="size-4 animate-spin" />
              <Save v-else class="size-4" />
              {{ loading.profile ? 'Saving...' : 'Update Profile' }}
            </button>
          </div>
        </div>

        <!-- APPEARANCE & DISPLAY -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden group hover:shadow-2xl transition-all duration-300">
          <div class="p-5 border-b bg-gradient-to-r from-violet-500 to-purple-600 flex items-center gap-3">
            <div class="p-2 bg-white/20 rounded-xl">
              <Monitor class="size-5 text-white" />
            </div>
            <h3 class="font-bold text-white text-lg">Appearance & Display</h3>
          </div>
          <div class="p-6 space-y-4">
            <div @click="toggleCompactSidebar" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all cursor-pointer group/item">
              <div class="flex items-center gap-4">
                <div class="size-12 bg-purple-50 rounded-xl flex items-center justify-center group-hover/item:bg-violet-500 group-hover/item:text-white transition-all duration-300">
                  <LayoutPanelLeft class="size-5 text-violet-500 group-hover/item:text-white transition-all" />
                </div>
                <div>
                  <p class="font-bold text-slate-800">Compact Sidebar</p>
                  <p class="text-xs text-slate-500">Reduce sidebar width for more space</p>
                </div>
              </div>
              <div :class="compactSidebar ? 'bg-violet-500' : 'bg-slate-300'" class="relative w-12 h-7 rounded-full transition-all duration-300 shadow-inner">
                <div :class="compactSidebar ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow"></div>
              </div>
            </div>

            <div @click="toggleNotifications" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all cursor-pointer group/item">
              <div class="flex items-center gap-4">
                <div class="size-12 bg-emerald-50 rounded-xl flex items-center justify-center group-hover/item:bg-emerald-500 group-hover/item:text-white transition-all duration-300">
                  <Bell class="size-5 text-emerald-500 group-hover/item:text-white transition-all" />
                </div>
                <div>
                  <p class="font-bold text-slate-800">Desktop Notifications</p>
                  <p class="text-xs text-slate-500">Receive in-app notifications</p>
                </div>
              </div>
              <div :class="notifications ? 'bg-emerald-500' : 'bg-slate-300'" class="relative w-12 h-7 rounded-full transition-all duration-300 shadow-inner">
                <div :class="notifications ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECURITY & LOGIN -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden group hover:shadow-2xl transition-all duration-300 lg:col-span-2">
          <div class="p-5 border-b bg-gradient-to-r from-emerald-500 to-teal-600 flex items-center gap-3">
            <div class="p-2 bg-white/20 rounded-xl">
              <Shield class="size-5 text-white" />
            </div>
            <h3 class="font-bold text-white text-lg">Security & Login</h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="flex items-center gap-4 p-5 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-100">
                <div class="size-12 bg-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/20">
                  <CheckCircle class="size-6 text-white" />
                </div>
                <div class="flex-1">
                  <p class="font-bold text-slate-800">OAuth2 Connected</p>
                  <p class="text-xs text-slate-500">Secured via SSO</p>
                </div>
                <span class="px-3 py-1.5 bg-green-500 text-white text-xs font-black rounded-full shadow-lg">Active</span>
              </div>
              
              <button @click="showLoginHistory = true" class="p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all border border-slate-200 text-center group/btn">
                <div class="size-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover/btn:bg-vilcom-blue group-hover/btn:text-white transition-all duration-300">
                  <History class="size-6 text-vilcom-blue group-hover/btn:text-white transition-all" />
                </div>
                <p class="font-bold text-slate-800">Login History</p>
                <p class="text-xs text-slate-500">View recent activity</p>
              </button>
              
              <button @click="logoutAllDevices" :disabled="loading.logout" class="p-5 bg-slate-50 rounded-xl hover:bg-red-50 transition-all border border-slate-200 text-center group/btn">
                <div class="size-12 bg-red-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover/btn:bg-red-500 group-hover/btn:text-white transition-all duration-300">
                  <Loader2 v-if="loading.logout" class="size-6 text-red-500 animate-spin" />
                  <LogOut v-else class="size-6 text-red-500 group-hover/btn:text-white transition-all" />
                </div>
                <p class="font-bold text-slate-800 group-hover/btn:text-red-600 transition-all">{{ loading.logout ? 'Processing...' : 'Logout All Devices' }}</p>
                <p class="text-xs text-slate-500 group-hover/btn:text-red-400 transition-all">Sign out everywhere</p>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- LOGIN HISTORY MODAL -->
      <div v-if="showLoginHistory" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showLoginHistory = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
          <div class="p-5 bg-gradient-to-r from-slate-800 to-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-white text-lg flex items-center gap-2">
              <History class="size-5" />
              Recent Login Activity
            </h3>
            <button @click="showLoginHistory = false" class="text-slate-400 hover:text-white transition-colors">
              <X class="size-5" />
            </button>
          </div>
          <div class="p-5 space-y-3 max-h-80 overflow-y-auto">
            <div v-for="(session, index) in loginHistory" :key="index" class="p-4 bg-slate-50 rounded-xl flex items-center gap-4 hover:bg-slate-100 transition-all">
              <div class="size-10 bg-vilcom-blue/10 rounded-full flex items-center justify-center">
                <Laptop class="size-5 text-vilcom-blue" />
              </div>
              <div class="flex-1">
                <p class="font-bold text-slate-800 text-sm">{{ session.device }}</p>
                <p class="text-xs text-slate-500">{{ session.location }} • {{ session.time }}</p>
              </div>
              <span :class="session.current ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-slate-200 text-slate-500'" class="px-3 py-1 text-xs font-bold rounded-full">{{ session.current ? 'Current' : session.date }}</span>
            </div>
          </div>
          <div class="p-4 border-t bg-slate-50">
            <button @click="showLoginHistory = false" class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold hover:bg-slate-700 transition-all">Close</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
  User, Mail, Save, Loader2, Monitor, LayoutPanelLeft, Bell, 
  Shield, CheckCircle, History, LogOut, X, Laptop
} from 'lucide-vue-next';

const loading = ref({ profile: false, logout: false });
const showLoginHistory = ref(false);
const notifications = ref(true);

const profileForm = ref({
  name: '',
  email: ''
});

const loginHistory = ref([
  { device: 'Chrome on Windows', location: 'Nairobi, KE', time: '2 hours ago', date: 'Today', current: true },
  { device: 'Safari on iPhone', location: 'Nairobi, KE', time: 'Yesterday', date: 'Yesterday', current: false },
  { device: 'Chrome on MacOS', location: 'Mombasa, KE', time: '3 days ago', date: 'Apr 11', current: false },
]);

onMounted(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  profileForm.value.name = userData.name || '';
  profileForm.value.email = userData.email || '';
  
  compactSidebar.value = localStorage.getItem('compactSidebar') === 'true';
  notifications.value = localStorage.getItem('notifications') !== 'false';
});

const toggleCompactSidebar = () => {
  compactSidebar.value = !compactSidebar.value;
  localStorage.setItem('compactSidebar', compactSidebar.value);
  window.dispatchEvent(new CustomEvent('toggle-compact-sidebar', { detail: compactSidebar.value }));
};

const toggleNotifications = () => {
  notifications.value = !notifications.value;
  localStorage.setItem('notifications', notifications.value);
};

const logoutAllDevices = async () => {
  if (!confirm('This will log you out from all devices except this one. Continue?')) return;
  
  loading.value.logout = true;
  try {
    await axios.post('/api/profile/logout-all');
    alert('All other devices have been logged out.');
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to logout devices');
  } finally {
    loading.value.logout = false;
  }
};

const updateProfile = async () => {
  loading.value.profile = true;
  try {
    const res = await axios.post('/api/profile/update', profileForm.value);
    localStorage.setItem('user_data', JSON.stringify(res.data.user));
    profileForm.value.name = res.data.user?.name || profileForm.value.name;
    profileForm.value.email = res.data.user?.email || profileForm.value.email;
    alert('Profile updated successfully!');
  } catch (err) {
    alert(err.response?.data?.message || 'Update failed');
  } finally {
    loading.value.profile = false;
  }
};
</script>
