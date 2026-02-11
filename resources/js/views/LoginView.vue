<template>
  <div class="auth-card">
    <div class="card-header">
      <p>Please enter your details to proceed.</p>
    </div>

    <form @submit.prevent="handleLogin" class="auth-form">
      <div class="input-group">
        <label>Email Address</label>
        <input v-model="form.email" type="email" placeholder="test@vilcom.co.ke" required />
      </div>

      <div class="input-group">
        <label>Password</label>
        <div style="position: relative; display: flex; align-items: center;">
          <input 
            v-model="form.password" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="enter a strong password" 
            required 
            style="padding-right: 50px;" 
          />
          <button 
            type="button" 
            @click="showPassword = !showPassword"
            style="position: absolute; right: 10px; background: none; border: none; color: #1e3a8a; cursor: pointer; font-size: 0.75rem; font-weight: 700;"
          >
            {{ showPassword ? 'HIDE' : 'SHOW' }}
          </button>
        </div>
      </div>

      <div class="forgot-container">
        <router-link to="/forgot-password" class="forgot-link">
          Forgot Password?
        </router-link>
      </div>

      <button type="submit" class="submit-btn" :disabled="loading">
        {{ loading ? 'Authenticating...' : 'Sign In' }}
      </button>
    </form>

    <div v-if="message" :class="['alert', isError ? 'alert-error' : 'alert-success']">
      {{ message }}
    </div>

    <p class="footer-text">
      New to Vilcom? <router-link to="/register">Create Account</router-link>
    </p>
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
const showPassword = ref(false); // Added: State to track password visibility
const router = useRouter();

const handleLogin = async () => {
  loading.value = true;
  message.value = '';
  try {
    const response = await axios.post('http://127.0.0.1:8000/api/login', form.value);
    
    localStorage.setItem('user_token', response.data.access_token);
    
    isError.value = false;
    message.value = "Login successful!";
    
    setTimeout(() => {
      router.push('/dashboard');
    }, 1000);

  } catch (error) {
    isError.value = true;
    // Updated: Logic to handle the 403 verification error from the backend
    message.value = error.response?.data?.message || "Invalid credentials.";
    
    // Redirect if verification is needed
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

<style scoped>
.auth-card {
  background: #ffffff;
  max-width: 380px;
  width: 100%;
  padding: 2.5rem 2rem;
  border-radius: 12px;
  border-top: 5px solid #f97316; /* Orange brand accent */
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.card-header p {
  color: #64748b;
  margin-bottom: 2rem;
  text-align: center;
}

.input-group { margin-bottom: 1.2rem; }

label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
  color: #475569;
}

input {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #1e3a8a; /* Deep blue focus color */
}

.forgot-container {
  text-align: right;
  margin-bottom: 1.5rem;
  margin-top: -0.5rem;
}

.forgot-link {
  font-size: 0.9rem;
  color: #1e3a8a;
  text-decoration: none;
  font-weight: 600;
}

.forgot-link:hover {
  color: #f97316; /* Orange hover effect */
}

.submit-btn {
  width: 100%;
  padding: 1rem;
  background: #f97316; /* Orange brand color */
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

.alert {
  margin-top: 1.5rem;
  padding: 0.75rem;
  border-radius: 6px;
  text-align: center;
  font-size: 0.9rem;
}

.alert-error { background: #fee2e2; color: #b91c1c; }
.alert-success { background: #dcfce7; color: #15803d; }

.footer-text { margin-top: 1.5rem; text-align: center; font-size: 0.9rem; }
.footer-text a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
</style>