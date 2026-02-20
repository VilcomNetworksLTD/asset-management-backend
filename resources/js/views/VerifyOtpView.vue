<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans text-gray-800">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border-t-[6px] border-[#f97316]">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-[#1e3a8a] mb-2">Verify Account</h2>
        <p v-if="displayEmail" class="text-sm text-gray-500 italic">
          Verifying code for: <strong class="text-gray-700">{{ displayEmail }}</strong>
        </p>
        <p v-else class="text-sm text-gray-500 italic">Enter the 6-digit code from your terminal.</p>
      </div>

      <form @submit.prevent="handleVerify" class="mt-8 space-y-6">
        <div class="space-y-1 text-left">
          <label class="block text-xs font-bold uppercase text-gray-500 tracking-tight ml-1">OTP Code</label>
          <input 
            v-model="otp" 
            type="text" 
            placeholder="000000" 
            maxlength="6" 
            required 
            class="w-full px-4 py-4 text-center text-3xl tracking-[12px] font-black rounded-lg border-2 border-gray-100 focus:border-[#1e3a8a] focus:outline-none transition-colors shadow-sm placeholder:tracking-normal placeholder:text-gray-300"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading" 
          class="w-full py-4 px-4 bg-[#f97316] hover:bg-[#ea580c] text-white font-bold rounded-lg shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Verifying...' : 'Confirm OTP' }}
        </button>
      </form>

      <div class="text-center mt-6">
        <p class="text-xs font-medium text-gray-500">
          Didn't receive a code? 
          <a 
            href="#" 
            @click.prevent="handleResend" 
            class="text-[#1e3a8a] font-bold hover:underline ml-1"
            :class="{ 'opacity-50 pointer-events-none': resendLoading }"
          >
            {{ resendLoading ? 'Sending...' : 'Resend OTP' }}
          </a>
        </p>
      </div>

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

const otp = ref('');
const displayEmail = ref('');
const message = ref('');
const loading = ref(false);
const resendLoading = ref(false);
const isError = ref(false);
const router = useRouter();

onMounted(() => {
  displayEmail.value = localStorage.getItem('pending_email') || '';
});

const handleVerify = async () => {
  loading.value = true;
  message.value = '';
  const email = localStorage.getItem('pending_email');

  if (!email) {
    isError.value = true;
    message.value = "Session expired. Please register again.";
    loading.value = false;
    return;
  }

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/verify-otp', {
      email: email, 
      otp_code: otp.value 
    });
    
    if (response.data.token) {
      const token = response.data.token;
      localStorage.setItem('user_token', token);

      try {
        const userResp = await axios.get('http://127.0.0.1:8000/api/user', {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (userResp.data) {
          localStorage.setItem('user_data', JSON.stringify(userResp.data));
        }
      } catch (fetchErr) {
        console.warn('Failed to fetch user after verification', fetchErr);
      }
    }

    isError.value = false;
    message.value = "Account verified! Welcome to Vilcom Asset Management.";
    localStorage.removeItem('pending_email');

    setTimeout(() => {
      const userData = localStorage.getItem('user_data');
      let role = 'staff';
      if (userData) {
        try {
          role = JSON.parse(userData).role || 'staff';
        } catch (e) {
          role = 'staff';
        }
      }
      const routeName = role === 'admin' ? 'dashboard-admin' : 'dashboard-user';
      router.push({ name: routeName });
    }, 1200);

  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Verification failed.";
  } finally {
    loading.value = false;
  }
};

const handleResend = async () => {
  const email = localStorage.getItem('pending_email');
  if (!email) {
    isError.value = true;
    message.value = "Email not found. Please register again.";
    return;
  }

  resendLoading.value = true;
  message.value = '';
  
  try {
    await axios.post('http://127.0.0.1:8000/api/resend-otp', { email });
    isError.value = false;
    message.value = "A new code has been sent to your terminal.";
  } catch (error) {
    isError.value = true;
    message.value = error.response?.data?.message || "Failed to resend code.";
  } finally {
    resendLoading.value = false;
  }
};
</script>