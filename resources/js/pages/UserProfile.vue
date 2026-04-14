<template>
  <div class="p-6 min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="p-6 bg-gradient-to-r from-vilcom-blue to-blue-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h2 class="text-2xl font-black text-white tracking-tight">My Profile</h2>
            <p class="text-blue-200 text-sm mt-1">Keep your personal and work details up to date.</p>
          </div>
          <span class="px-4 py-2 rounded-xl text-xs font-black uppercase bg-white/20 text-white border border-white/30 backdrop-blur">
            {{ user.role || 'staff' }}
          </span>
        </div>

        <!-- Profile Card -->
        <div class="p-6">
          <div class="flex items-center gap-5 p-6 bg-gradient-to-r from-slate-50 to-blue-50 rounded-2xl border border-slate-100 mb-6">
            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&background=1e40af&color=fff&size=128`" class="size-20 rounded-2xl border-3 border-vilcom-blue shadow-lg" alt="Avatar">
            <div>
              <p class="font-black text-slate-800 text-xl">{{ user.name || 'Your Name' }}</p>
              <p class="text-sm text-slate-500">{{ user.email || 'your@email.com' }}</p>
              <div class="flex items-center gap-2 mt-2">
                <span v-if="user.department?.name" class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-vilcom-blue border border-blue-200">
                  {{ user.department.name }}
                </span>
              </div>
            </div>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all border border-slate-100">
              <div class="flex items-center gap-3 mb-2">
                <div class="size-8 bg-blue-50 rounded-lg flex items-center justify-center">
                  <User class="size-4 text-vilcom-blue" />
                </div>
                <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Full Name</label>
              </div>
              <p class="font-bold text-slate-800 text-lg ml-11">{{ user.name || '—' }}</p>
            </div>
            <div class="p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all border border-slate-100">
              <div class="flex items-center gap-3 mb-2">
                <div class="size-8 bg-blue-50 rounded-lg flex items-center justify-center">
                  <Mail class="size-4 text-vilcom-blue" />
                </div>
                <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Email</label>
              </div>
              <p class="font-bold text-slate-800 text-lg ml-11">{{ user.email || '—' }}</p>
            </div>
            <div class="p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all border border-slate-100">
              <div class="flex items-center gap-3 mb-2">
                <div class="size-8 bg-orange-50 rounded-lg flex items-center justify-center">
                  <Briefcase class="size-4 text-vilcom-orange" />
                </div>
                <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Department</label>
              </div>
              <p class="font-bold text-slate-800 text-lg ml-11">{{ user.department?.name || user.role || 'Staff Member' }}</p>
            </div>
            <div class="p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-all border border-slate-100">
              <div class="flex items-center gap-3 mb-2">
                <div class="size-8 bg-green-50 rounded-lg flex items-center justify-center">
                  <ShieldCheck class="size-4 text-green-600" />
                </div>
                <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Account Status</label>
              </div>
              <p class="font-bold text-slate-800 text-lg ml-11">{{ user.is_verified ? 'Active' : 'Pending Verification' }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-3">
            <button @click="$router.push('/dashboard/user/settings')" class="flex-1 bg-vilcom-blue text-white px-6 py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-900/10">
              <Edit class="size-4" />
              Edit Profile
            </button>
            <button @click="refreshProfile" class="px-6 py-4 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
              <RefreshCw class="size-4" />
              Refresh
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
import { User, Mail, Briefcase, ShieldCheck, Edit, RefreshCw } from 'lucide-vue-next';

const user = ref({});

const refreshProfile = async () => {
  try {
    const { data } = await axios.get('/api/profile')
    user.value = data || {}
    localStorage.setItem('user_data', JSON.stringify(data || {}))
  } catch {
    user.value = JSON.parse(localStorage.getItem('user_data') || '{}')
  }
}

onMounted(refreshProfile)
</script>
