<template>
  <div class="auth-card">
    <div class="card-header">
      <p>Please enter your details to proceed.</p>
    </div>

    <form @submit.prevent="handleRegister" class="auth-form">
      <div class="input-group">
        <label>Full Name</label>
        <input v-model="form.name" type="text" placeholder="John Doe" required />
      </div>

      <div class="input-group">
        <label>Email Address</label>
        <input v-model="form.email" type="email" placeholder="john@vilcom.co.ke" required />
      </div>

      <div class="input-row">
        <div class="input-group">
          <label>Password</label>
          <div style="position: relative; display: flex; align-items: center;">
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="" 
              required 
              style="padding-right: 45px;"
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword"
              style="position: absolute; right: 10px; background: none; border: none; color: #1e3a8a; cursor: pointer; font-size: 0.7rem; font-weight: 700;"
            >
              {{ showPassword ? 'HIDE' : 'SHOW' }}
            </button>
          </div>
        </div>

        <div class="input-group">
          <label>Confirm</label>
          <div style="position: relative; display: flex; align-items: center;">
            <input 
              v-model="form.password_confirmation" 
              :type="showConfirmPassword ? 'text' : 'password'" 
              placeholder="" 
              required 
              style="padding-right: 45px;"
            />
            <button 
              type="button" 
              @click="showConfirmPassword = !showConfirmPassword"
              style="position: absolute; right: 10px; background: none; border: none; color: #1e3a8a; cursor: pointer; font-size: 0.7rem; font-weight: 700;"
            >
              {{ showConfirmPassword ? 'HIDE' : 'SHOW' }}
            </button>
          </div>
        </div>
      </div>

      <button type="submit" class="submit-btn" :disabled="loading">
        {{ loading ? 'Creating Account...' : 'Register Now' }}
      </button>
    </form>

    <div v-if="isError" class="alert alert-error">
      {{ message }}
    </div>

    <p class="footer-text">
      Already have an account? <router-link to="/">Sign In</router-link>
    </p>
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

// Separate visibility states for each password field
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const router = useRouter();

const handleRegister = async () => {
  loading.value = true;
  message.value = '';
  isError.value = false;
  
  try {
    // API call remains targeted to your Laravel backend
    await axios.post('http://127.0.0.1:8000/api/register', form.value);
    
    // Save email so the OTP page can use it for verification
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

<style scoped>
.auth-card {
  background: #ffffff;
  max-width: 520px;
  width: 100%;
  padding: 3.5rem 3rem;
  border-radius: 16px;
  border-top: 6px solid #f97316; /* Orange accent */
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
}
.input-row { display: flex; gap: 1.5rem; }
.input-group { margin-bottom: 1.8rem; width: 100%; }
label { display: block; font-size: 0.95rem; font-weight: 700; margin-bottom: 0.6rem; color: #475569; }
input { width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; box-sizing: border-box; }
input:focus { outline: none; border-color: #1e3a8a; } /* Deep blue focus */
.submit-btn { width: 100%; padding: 1.2rem; background: #f97316; color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 1rem; }
.alert { margin-top: 1.5rem; padding: 1rem; border-radius: 8px; text-align: center; }
.alert-error { background: #fee2e2; color: #b91c1c; }
.footer-text { margin-top: 1.5rem; text-align: center; font-size: 0.9rem; }
.footer-text a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
</style>