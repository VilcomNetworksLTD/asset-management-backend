<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto space-y-6">
      
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b bg-gray-50">
          <h3 class="font-bold text-gray-700"><i class="fa fa-user-cog mr-2"></i> Account Information</h3>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
            <input v-model="profileForm.name" type="text" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
            <input v-model="profileForm.email" type="email" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none">
          </div>
          <button @click="updateProfile" :disabled="loading.profile" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 disabled:opacity-50">
            {{ loading.profile ? 'Saving...' : 'Update Profile' }}
          </button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b bg-gray-50 text-red-600">
          <h3 class="font-bold"><i class="fa fa-lock mr-2"></i> Change Password</h3>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Current Password</label>
            <input v-model="passwordForm.current_password" type="password" class="w-full border rounded p-2 focus:ring-2 focus:ring-red-500 outline-none">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">New Password</label>
              <input v-model="passwordForm.new_password" type="password" class="w-full border rounded p-2 focus:ring-2 focus:ring-red-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Confirm New Password</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" class="w-full border rounded p-2 focus:ring-2 focus:ring-red-500 outline-none">
            </div>
          </div>
          <button @click="changePassword" :disabled="loading.password" class="bg-gray-800 text-white px-6 py-2 rounded font-bold hover:bg-black disabled:opacity-50">
            {{ loading.password ? 'Updating...' : 'Change Password' }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref({ profile: false, password: false });

const profileForm = ref({
  name: '',
  email: ''
});

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

onMounted(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  profileForm.value.name = userData.name || '';
  profileForm.value.email = userData.email || '';
});

const updateProfile = async () => {
  loading.value.profile = true;
  try {
    const res = await axios.post('/api/profile/update', profileForm.value);
    localStorage.setItem('user_data', JSON.stringify(res.data.user));
    profileForm.value.name = res.data.user?.name || profileForm.value.name
    profileForm.value.email = res.data.user?.email || profileForm.value.email
    alert('Profile updated successfully!');
  } catch (err) {
    alert(err.response?.data?.message || 'Update failed');
  } finally {
    loading.value.profile = false;
  }
};

const changePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    alert('New passwords do not match');
    return;
  }

  loading.value.password = true;
  try {
    await axios.post('/api/profile/password', passwordForm.value);
    alert('Password updated successfully!');
    // Clear the form
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
  } catch (err) {
    alert(err.response?.data?.message || 'Password update failed');
  } finally {
    loading.value.password = false;
  }
};
</script>