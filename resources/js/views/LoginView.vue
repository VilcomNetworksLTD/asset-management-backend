<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <div class="flex items-center justify-center gap-3 mb-2">
          <img :src="logoUrl" alt="Vilcom Logo" class="h-10 w-10 object-contain" />
          <div class="text-left">
            <div class="text-xl font-extrabold text-[#1e3a8a] leading-none">AMS</div>
            <div class="text-xs text-gray-600 leading-tight">Vilcom Asset Management System</div>
          </div>
        </div>
        <p class="text-sm text-gray-500 italic">Please enter your details to proceed.</p>
      </div>

      <form @submit.prevent="handleLogin" class="mt-8 space-y-5">
        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Email Address</label>
          <input 
            v-model="form.email" 
            type="email" 
            placeholder="test@vilcom.co.ke" 
            required 
            class="w-full px-4 py-3 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight">Password</label>
          <div class="relative flex items-center">
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="enter a strong password" 
              required 
              class="w-full px-4 py-3 pr-16 rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors"
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

        <div class="flex justify-end">
          <router-link to="/forgot-password" class="text-xs font-bold text-[#1e3a8a] hover:text-[#f97316]">
            Forgot Password?
          </router-link>
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-3 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Authenticating...' : 'Sign In' }}
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

      <p class="mt-8 text-center text-xs font-medium text-gray-500">
        New to Vilcom? 
        <router-link to="/register" class="text-[#1e3a8a] font-bold hover:underline ml-1">Create Account</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const form = ref({ email: '', password: '' });
const message = ref('');
const loading = ref(false);
const isError = ref(false);
const showPassword = ref(false); 
const router = useRouter();
const logoUrl = '/Vlogo.jpeg';

const handleLogin = async () => {
  loading.value = true;
  message.value = '';
  try {
    const response = await axios.post('http://127.0.0.1:8000/api/login', form.value);
    
    localStorage.setItem('user_token', response.data.token);
    localStorage.setItem('user_data', JSON.stringify(response.data.user));
    
    isError.value = false;
    message.value = "Login successful!";
    
    setTimeout(() => {
      const role = response.data.user?.role || 'staff';
      const routeName = role === 'admin' ? 'dashboard-admin' : 'dashboard-user';
      router.push({ name: routeName });
    }, 1000);

  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Invalid credentials.";
    
    if (error.response?.status === 403 && error.response?.data?.needs_verification) {
        setTimeout(() => {
            router.push('/verify-otp');
        }, 1500);
    }
  } finally {
    loading.value = false;
  }
};
</script>