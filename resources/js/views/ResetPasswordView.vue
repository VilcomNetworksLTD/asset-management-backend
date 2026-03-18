<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <h2 class="text-2xl font-extrabold text-[#1e3a8a] mb-2">Reset Password</h2>
        <p class="text-sm text-gray-500 italic">Please enter your new password below.</p>
      </div>

      <form @submit.prevent="handleReset" class="mt-8 space-y-5">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">New Password</label>
          <input 
            v-model="form.password" 
            type="password" 
            placeholder="enter a strong password" 
            required 
            minlength="8"
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Confirm Password</label>
          <input 
            v-model="form.password_confirmation" 
            type="password" 
            placeholder="confirm your new password" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-3 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Updating...' : 'Reset Password' }}
        </button>
      </form>

      <div 
        v-if="message" 
        :class="[
          'mt-6 p-3 rounded-lg text-center text-sm font-semibold', 
          isError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'
        ]"
      >
        {{ message }}
      </div>

      <div class="text-center mt-6">
        <router-link to="/" class="text-xs font-bold text-[#1e3a8a] hover:text-[#f97316]">
          Back to Login
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const form = ref({
  email: '',
  token: '',
  password: '',
  password_confirmation: ''
});

const message = ref('');
const loading = ref(false);
const isError = ref(false);

onMounted(() => {
  form.value.email = route.query.email || '';
  form.value.token = route.query.token || '';

  if (!form.value.email || !form.value.token) {
    isError.value = true;
    message.value = "Invalid or expired reset link. Please request a new one.";
  }
});

const handleReset = async () => {
  loading.value = true;
  message.value = '';
  isError.value = false;

  try {
    const response = await axios.post('/api/reset-password', form.value);
    
    isError.value = false;
    message.value = response.data.message || "Password reset successfully!";
    
    setTimeout(() => {
      router.push({ name: 'login' });
    }, 2000);

  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Failed to reset password. The link may have expired.";
  } finally {
    loading.value = false;
  }
};
</script>