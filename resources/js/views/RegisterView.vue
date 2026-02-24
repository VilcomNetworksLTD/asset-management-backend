<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-xl w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <h1 class="text-3xl font-extrabold text-[#1e3a8a] mb-2">Create Account</h1>
        <p class="text-sm text-gray-500 italic">Please enter your details to proceed.</p>
      </div>

      <form @submit.prevent="handleRegister" class="mt-8 space-y-6">
        <div class="space-y-5">
          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Full Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="John Doe" 
              required 
              class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Email Address</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="john@vilcom.co.ke" 
              required 
              class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
            />
          </div>

          <div class="flex flex-col md:flex-row gap-5">
            <div class="w-full space-y-1">
              <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Password</label>
              <div class="relative flex items-center">
                <input 
                  v-model="form.password" 
                  :type="showPassword ? 'text' : 'password'" 
                  required 
                  class="w-full px-4 py-3 pr-14 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
                />
                <button 
                  type="button" 
                  @click="showPassword = !showPassword"
                  class="absolute right-3 text-[10px] font-black text-[#1e3a8a] hover:text-[#f97316] transition-colors"
                >
                  {{ showPassword ? 'HIDE' : 'SHOW' }}
                </button>
              </div>
            </div>

            <div class="w-full space-y-1">
              <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Confirm</label>
              <div class="relative flex items-center">
                <input 
                  v-model="form.password_confirmation" 
                  :type="showConfirmPassword ? 'text' : 'password'" 
                  required 
                  class="w-full px-4 py-3 pr-14 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm"
                />
                <button 
                  type="button" 
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3 text-[10px] font-black text-[#1e3a8a] hover:text-[#f97316] transition-colors"
                >
                  {{ showConfirmPassword ? 'HIDE' : 'SHOW' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-4 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-2"
        >
          {{ loading ? 'Creating Account...' : 'Register Now' }}
        </button>
      </form>

      <div 
        v-if="isError" 
        class="mt-6 p-3 rounded-lg text-center text-sm font-semibold bg-red-50 text-red-700 border border-red-100"
      >
        {{ message }}
      </div>

      <p class="mt-8 text-center text-xs font-medium text-gray-500">
        Already have an account? 
        <router-link to="/" class="text-[#1e3a8a] font-bold hover:underline ml-1">Sign In</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const message = ref('');
const loading = ref(false);
const isError = ref(false);

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const router = useRouter();

const handleRegister = async () => {
  loading.value = true;
  message.value = '';
  isError.value = false;
  
  try {
    await axios.post('http://127.0.0.1:8000/api/register', form.value);
    localStorage.setItem('pending_email', form.value.email);
    router.push('/verify-otp'); 
  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Registration failed.";
  } finally {
    loading.value = false;
  }
};
</script>