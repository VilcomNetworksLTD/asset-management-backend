<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-[#1e3a8a] mb-2">Reset Password</h2>
        <p v-if="displayEmail" class="text-sm text-gray-500 italic">
          Enter the code sent to: <strong class="text-gray-700">{{ displayEmail }}</strong>
        </p>
      </div>

      <form @submit.prevent="handleReset" class="mt-8 space-y-5">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">OTP Code</label>
          <input 
            v-model="form.otp_code" 
            type="text" 
            placeholder="000000" 
            maxlength="6" 
            required 
            class="w-full px-4 py-3 text-center text-2xl tracking-[10px] font-black rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm placeholder:tracking-normal placeholder:text-gray-300"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">New Password</label>
          <input 
            v-model="form.password" 
            type="password" 
            placeholder="••••••••" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Confirm Password</label>
          <input 
            v-model="form.password_confirmation" 
            type="password" 
            placeholder="••••••••" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-3.5 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-2"
        >
          {{ loading ? 'Updating...' : 'Save New Password' }}
        </button>
      </form>

      <div 
        v-if="message" 
        :class="[
          'mt-6 p-3 rounded-lg text-center text-sm font-semibold border', 
          isError ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'
        ]"
      >
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const displayEmail = ref('');
const loading = ref(false);
const message = ref('');
const isError = ref(false);

const form = ref({
  email: '',
  otp_code: '', 
  password: '',
  password_confirmation: ''
});

onMounted(() => {
  const savedEmail = localStorage.getItem('reset_email');
  if (savedEmail) {
    form.value.email = savedEmail;
    displayEmail.value = savedEmail;
  }
});

const handleReset = async () => {
  if (!form.value.email) {
    isError.value = true;
    message.value = "Session expired. Please request a new code.";
    return;
  }

  loading.value = true;
  message.value = '';
  isError.value = false;

  try {
    await axios.post('http://127.0.0.1:8000/api/reset-password', form.value);
    
    isError.value = false;
    message.value = "Password updated! Redirecting to login...";
    localStorage.removeItem('reset_email');

    setTimeout(() => {
      router.push('/');
    }, 2000);

  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Invalid code or password mismatch.";
  } finally {
    loading.value = false;
  }
};
</script>