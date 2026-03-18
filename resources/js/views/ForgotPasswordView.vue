<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <h2 class="text-2xl font-extrabold text-[#1e3a8a] mb-2">Forgot Password</h2>
        <p class="text-sm text-gray-500 italic">Enter your email to receive a password reset link.</p>
      </div>

      <form @submit.prevent="handleForgot" class="mt-8 space-y-6">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Email Address</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="enter your registered email" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-3 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Processing...' : 'Send Reset Link' }}
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
import { ref } from 'vue';
import axios from 'axios';

const email = ref('');
const message = ref('');
const loading = ref(false);
const isError = ref(false);

const handleForgot = async () => {
  loading.value = true;
  message.value = '';
  isError.value = false;

  try {
    const response = await axios.post('/api/forgot-password', { email: email.value });
    message.value = response.data.message || "If your account exists, a reset link has been sent.";
  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Failed to send reset link. Please try again later.";
  } finally {
    loading.value = false;
  }
};
</script>