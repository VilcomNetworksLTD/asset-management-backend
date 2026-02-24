<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow p-6 md:p-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">My Profile</h2>
          <p class="text-sm text-gray-500">Keep your personal and work details up to date.</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-blue-100 text-blue-700">
          {{ user.role || 'staff' }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-50 p-4 rounded border">
          <label class="text-xs font-bold text-gray-400 uppercase">Full Name</label>
          <p class="text-gray-800 font-semibold mt-1">{{ user.name || '—' }}</p>
        </div>
        <div class="bg-gray-50 p-4 rounded border">
          <label class="text-xs font-bold text-gray-400 uppercase">Email</label>
          <p class="text-gray-800 font-semibold mt-1">{{ user.email || '—' }}</p>
        </div>
        <div class="bg-gray-50 p-4 rounded border">
          <label class="text-xs font-bold text-gray-400 uppercase">Employee ID</label>
          <p class="text-gray-800 font-semibold mt-1">#{{ String(user.id || '').padStart(4, '0') }}</p>
        </div>
        <div class="bg-gray-50 p-4 rounded border">
          <label class="text-xs font-bold text-gray-400 uppercase">Account Status</label>
          <p class="text-gray-800 font-semibold mt-1">{{ user.is_verified ? 'Active' : 'Pending Verification' }}</p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3">
        <button @click="$router.push('/dashboard/user/settings')" class="bg-gray-900 text-white px-4 py-2 rounded font-bold hover:bg-black transition">
          Edit Profile & Password
        </button>
        <button @click="refreshProfile" class="bg-white border px-4 py-2 rounded font-semibold text-gray-700 hover:bg-gray-50">
          Refresh
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
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