<template>
  <div class="min-h-screen bg-[#ecf0f5] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
    
    <div class="max-w-md w-full bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-[#1e3a8a] mb-2">Forgot Password?</h2>
        <p class="text-sm text-gray-500 italic">
          Enter your email to receive a password reset OTP code.
        </p>
      </div>

      <form @submit.prevent="handleForgot" class="space-y-6">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight ml-1">Email Address</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="dot@gmail.com" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-4 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Sending Code...' : 'Send Reset OTP' }}
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

      <div class="text-center mt-6">
        <p class="text-xs font-medium text-gray-500">
          Remembered your password? 
          <router-link to="/" class="text-[#1e3a8a] font-bold hover:underline ml-1">Sign In</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const email = ref('');
const message = ref('');
const loading = ref(false);
const isError = ref(false);
const router = useRouter();

const handleForgot = async () => {
  loading.value = true;
  message.value = '';
  isError.value = false;

  try {
    // API call to send OTP
    await axios.post('http://127.0.0.1:8000/api/forgot-password', { email: email.value });
    
    // Logic preserved: Save email for the reset page
    localStorage.setItem('reset_email', email.value);
    
    message.value = "OTP sent successfully! Redirecting...";
    
    // Logic preserved: Move to reset page after 2 seconds
    setTimeout(() => {
      router.push('/reset-password');
    }, 2000);

  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Account not found or error sending OTP.";
  } finally {
    loading.value = false;
  }
};
</script>