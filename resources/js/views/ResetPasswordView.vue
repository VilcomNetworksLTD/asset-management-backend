<template>
  <div class="auth-card">
    <div class="card-header">
      <h2>Reset Password</h2>
      <p v-if="displayEmail" class="subtitle">
        Enter the code sent to: <strong>{{ displayEmail }}</strong>
      </p>
    </div>

    <form @submit.prevent="handleReset" class="auth-form">
      <div class="input-group">
        <label>OTP Code</label>
        <input 
          v-model="form.otp_code" 
          type="text" 
          placeholder="000000" 
          maxlength="6" 
          required 
          class="otp-input-styled"
        />
      </div>

      <div class="input-group">
        <label>New Password</label>
        <input v-model="form.password" type="password" placeholder="••••••••" required />
      </div>

      <div class="input-group">
        <label>Confirm Password</label>
        <input v-model="form.password_confirmation" type="password" placeholder="••••••••" required />
      </div>

      <button type="submit" class="submit-btn" :disabled="loading">
        {{ loading ? 'Updating...' : 'Save New Password' }}
      </button>
    </form>

    <div v-if="message" :class="['alert', isError ? 'alert-error' : 'alert-success']">
      {{ message }}
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
  otp_code: '', // Required by your backend
  password: '',
  password_confirmation: ''
});

onMounted(() => {
  const savedEmail = localStorage.getItem('reset_email');
  if (savedEmail) {
    form.value.email = savedEmail;
    displayEmail.value = savedEmail;
  }
  // No auto-redirect here so the page stays open for you!
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

<style scoped>
/* Refined styles to match Vilcom branding */
.auth-card {
  background: #ffffff;
  max-width: 420px;
  width: 100%;
  padding: 3rem 2.5rem;
  border-radius: 16px;
  border-top: 6px solid #f97316; /* Orange brand */
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.card-header h2 { color: #1e3a8a; margin-bottom: 0.5rem; }
.subtitle { color: #64748b; margin-bottom: 2rem; font-size: 0.9rem; }

.input-group { margin-bottom: 1.5rem; text-align: left; }
label { display: block; font-weight: 700; margin-bottom: 0.4rem; color: #475569; font-size: 0.85rem; }

input { 
  width: 100%; 
  padding: 0.9rem; 
  border: 2px solid #e2e8f0; 
  border-radius: 10px; 
  box-sizing: border-box; 
}

.otp-input-styled {
  text-align: center;
  font-size: 1.5rem;
  letter-spacing: 6px;
  font-weight: 800;
  border-color: #cbd5e1;
}

.submit-btn {
  width: 100%;
  padding: 1.1rem;
  background: #f97316; /* Vibrant Orange */
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  font-size: 1rem;
}

.alert { margin-top: 1.5rem; padding: 1rem; border-radius: 8px; font-size: 0.9rem; }
.alert-error { background: #fee2e2; color: #b91c1c; }
.alert-success { background: #dcfce7; color: #15803d; }
</style>