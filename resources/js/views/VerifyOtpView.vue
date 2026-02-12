<template>
  <div class="auth-card">
    <div class="card-header">
      <h2>Verify Account</h2>
      <p v-if="displayEmail">
        Verifying code for: <strong>{{ displayEmail }}</strong>
      </p>
      <p v-else>Enter the 6-digit code from your terminal.</p>
    </div>

    <form @submit.prevent="handleVerify" class="auth-form">
      <div class="input-group">
        <label>OTP Code</label>
        <input 
          v-model="otp" 
          type="text" 
          placeholder="000000" 
          maxlength="6" 
          required 
          class="otp-input"
        />
      </div>

      <button type="submit" class="submit-btn" :disabled="loading">
        {{ loading ? 'Verifying...' : 'Confirm OTP' }}
      </button>
    </form>

    <p class="resend-text">
      Didn't receive a code? 
      <a href="#" @click.prevent="handleResend" :class="{ 'disabled-link': resendLoading }">
        {{ resendLoading ? 'Sending...' : 'Resend OTP' }}
      </a>
    </p>

    <div v-if="message" :class="['alert', isError ? 'alert-error' : 'alert-success']">
      {{ message }}
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
    // Correctly sending email and otp_code to match backend
    const response = await axios.post('http://127.0.0.1:8000/api/verify-otp', {
      email: email, 
      otp_code: otp.value 
    });
    
    // NEW: Save the token and log them in automatically
    if (response.data.token) {
        localStorage.setItem('user_token', response.data.token);
    }
    
    isError.value = false;
    message.value = "Account verified! Welcome to Vilcom Asset Management."; // Updated message
    
    localStorage.removeItem('pending_email');

    // NEW: Redirect straight to dashboard
    setTimeout(() => {
      router.push('/dashboard'); 
    }, 2000);

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

<style scoped>
.auth-card {
  background: #ffffff;
  max-width: 440px;
  width: 100%;
  padding: 3.5rem 3rem;
  border-radius: 16px;
  border-top: 6px solid #f97316; /* Orange brand color */
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.card-header h2 { 
  color: #1e3a8a; /* Deep Blue brand color */
  margin: 0 0 0.5rem 0; 
  font-size: 1.8rem; 
}

.card-header p { color: #64748b; margin-bottom: 2rem; }

.otp-input { 
  width: 100%; 
  padding: 1rem; 
  border: 2px solid #e2e8f0; 
  border-radius: 10px; 
  text-align: center; 
  font-size: 2rem; 
  letter-spacing: 8px; 
  font-weight: 800; 
  box-sizing: border-box; 
}

.submit-btn { 
  width: 100%; 
  padding: 1.2rem; 
  background: #f97316; /* Orange button */
  color: white; 
  border: none; 
  border-radius: 10px; 
  font-size: 1.1rem; 
  font-weight: 700; 
  cursor: pointer; 
  margin-top: 1rem; 
}

.resend-text {
  margin-top: 1.5rem;
  font-size: 0.9rem;
  color: #64748b;
}

.resend-text a {
  color: #1e3a8a;
  text-decoration: none;
  font-weight: 700;
}

.disabled-link {
  opacity: 0.5;
  pointer-events: none;
  cursor: not-allowed;
}

.alert { margin-top: 1.5rem; padding: 1rem; border-radius: 8px; font-size: 0.95rem; }
.alert-error { background: #fee2e2; color: #b91c1c; }
.alert-success { background: #dcfce7; color: #15803d; }
</style>