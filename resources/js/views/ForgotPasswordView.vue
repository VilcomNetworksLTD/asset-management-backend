<template>
  <div class="auth-card">
    <div class="card-header">
      <h2>Forgot Password?</h2>
      <p>Enter your email to receive a password reset OTP code.</p>
    </div>

    <form @submit.prevent="handleForgot" class="auth-form">
      <div class="input-group">
        <label>Email Address</label>
        <input v-model="email" type="email" placeholder="dot@gmail.com" required />
      </div>

      <button type="submit" class="submit-btn" :disabled="loading">
        {{ loading ? 'Sending Code...' : 'Send Reset OTP' }}
      </button>
    </form>

    <div v-if="message" :class="['alert', isError ? 'alert-error' : 'alert-success']">
      {{ message }}
    </div>

    <p class="footer-text">
      Remembered your password? <router-link to="/">Sign In</router-link>
    </p>
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
    
    // Save email so the next page knows who is resetting
    localStorage.setItem('reset_email', email.value);
    
    message.value = "OTP sent successfully! Redirecting...";
    
    // Move to the actual reset page where they enter OTP + New Password
    setTimeout(() => {
      router.push('/reset-password');
    }, 2000);

  } catch (error) {
    isError.value = true;
    // Specifically show if the account doesn't exist based on backend response
    message.value = error.response?.data?.message || "Account not found or error sending OTP.";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Standard brand styles */
.auth-card {
  background: #ffffff;
  max-width: 440px;
  width: 100%;
  padding: 3.5rem 3rem;
  border-radius: 16px;
  border-top: 6px solid #f97316;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
}
.card-header h2 { color: #1e3a8a; margin-bottom: 0.5rem; text-align: center;}
.card-header p { color: #64748b; margin-bottom: 2rem; text-align: center; font-size: 0.95rem;}
.input-group { margin-bottom: 1.5rem; }
label { display: block; font-weight: 700; margin-bottom: 0.5rem; color: #475569; }
input { width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; }
.submit-btn { width: 100%; padding: 1.2rem; background: #f97316; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
.alert { margin-top: 1.5rem; padding: 1rem; border-radius: 8px; text-align: center; }
.alert-error { background: #fee2e2; color: #b91c1c; }
.alert-success { background: #dcfce7; color: #15803d; }
.footer-text { margin-top: 1.5rem; text-align: center; font-size: 0.9rem; }
.footer-text a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
</style>