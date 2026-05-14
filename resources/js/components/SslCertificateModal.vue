<template>
  <Modal :show="show" @close="close" title="Security Protocol Initialization">
    <div class="space-y-8">
      <div class="flex items-center gap-4 p-6 bg-blue-50/50 rounded-3xl border border-blue-100">
        <div class="p-3 bg-vilcom-blue text-white rounded-2xl shadow-lg shadow-blue-900/20">
          <ShieldCheck class="size-6" />
        </div>
        <div>
          <h3 class="text-lg font-black text-slate-800 tracking-tight">SSL/TLS Deployment</h3>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Registering Encryption Headers</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="space-y-2">
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Domain Topology (Common Name)</label>
          <div class="flex gap-3">
            <input v-model="form.common_name" class="flex-1 bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="e.g. security.vilcom.co" required>
            <button type="button" @click="scanDomain" class="px-6 py-4 bg-slate-800 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all flex items-center gap-2" :disabled="scanning || !form.common_name">
              <RefreshCw v-if="scanning" class="size-4 animate-spin" />
              <Search v-else class="size-4" />
              {{ scanning ? 'Scanning...' : 'Auto-Scan' }}
            </button>
          </div>
          <p v-if="errors.common_name" class="text-red-500 text-[10px] font-bold uppercase tracking-widest mt-1 ml-1">{{ errors.common_name[0] }}</p>
        </div>

        <div v-if="form.serial_number" class="p-5 bg-teal-50/50 border border-teal-100 rounded-2xl flex items-center gap-4 animate-in fade-in zoom-in">
          <div class="size-10 bg-white rounded-xl flex items-center justify-center text-teal-600 shadow-sm">
            <CheckCircle2 class="size-5" />
          </div>
          <div>
            <div class="text-xs font-black text-slate-800">{{ form.ca_vendor }} Authority Detected</div>
            <div class="text-[9px] font-bold text-teal-600 uppercase tracking-widest mt-0.5 font-mono">Serial: {{ form.serial_number.substring(0,16).toUpperCase() }}...</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Communication Port</label>
            <input v-model="form.port" type="number" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all">
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Lifecycle Termination (Expiry)</label>
            <input v-model="form.expiry_date" type="date" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" required>
            <p v-if="errors.expiry_date" class="text-red-500 text-[10px] font-bold uppercase tracking-widest mt-1 ml-1">{{ errors.expiry_date[0] }}</p>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Root Authority (Vendor)</label>
            <input v-model="form.ca_vendor" type="text" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="e.g. DigiCert, Let's Encrypt">
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Infrastructure Category</label>
            <select v-model="form.installed_on_type" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all cursor-pointer">
              <option value="web_server">Web Server Node</option>
              <option value="load_balancer">Load Balancer</option>
              <option value="application">Application Layer</option>
              <option value="cdn">Content Delivery (CDN)</option>
              <option value="other">Other Architecture</option>
            </select>
          </div>

          <div class="space-y-2 md:col-span-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Host Deployment Identifier</label>
            <input v-model="form.installed_on" type="text" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all" placeholder="e.g. production-api-01">
          </div>

          <div class="space-y-2 md:col-span-2">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Administrative Owner</label>
            <select v-model="form.assigned_owner_id" class="w-full bg-gray-50 border-none rounded-2xl py-4 px-6 text-sm font-bold focus:ring-2 focus:ring-vilcom-blue/20 transition-all cursor-pointer">
              <option :value="null">-- Unassigned Architecture --</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.department?.name || user.role || 'Staff' }})</option>
            </select>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-50">
          <button @click="submit" type="button" class="flex-1 py-4 bg-vilcom-blue text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
            Execute Protocol Save
          </button>
          <button @click="close" type="button" class="px-10 py-4 bg-gray-100 text-gray-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 hover:text-slate-600 transition-all">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import Modal from './Modal.vue';
import { ShieldCheck, RefreshCw, Search, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  users: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'saved']);

// Function to return a fresh initial state
const getInitialState = () => ({
  common_name: '',
  port: 443,
  expiry_date: '',
  ca_vendor: '',
  serial_number: '',
  installed_on: '',
  installed_on_type: 'web_server', 
  assigned_owner_id: null
});

const form = reactive(getInitialState());
const errors = ref({});
const scanning = ref(false);

const close = () => { 
  emit('close'); 
  resetForm(); 
};

const resetForm = () => { 
  Object.assign(form, getInitialState()); 
  errors.value = {}; 
};

const submit = async () => {
  try {
    errors.value = {};
    console.log("Payload being sent to server:", { ...form });
    await axios.post('/api/ssl-certificates', form);
    emit('saved');
    close();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    console.error("Submission error:", error.response?.data);
  }
};

const scanDomain = async () => {
  if (!form.common_name) return;
  scanning.value = true;
  try {
    const { data } = await axios.post('/api/ssl-certificates/scan-domain', { 
      host: form.common_name, 
      port: form.port 
    });
    
    if (data.expiry_date) form.expiry_date = data.expiry_date;
    if (data.ca_vendor) form.ca_vendor = data.ca_vendor;
    if (data.serial_number) form.serial_number = data.serial_number;
    if (data.common_name) form.common_name = data.common_name;
    
  } catch (e) { 
    alert("Scan failed. Please check the domain/port."); 
  } finally { 
    scanning.value = false; 
  }
};
</script>