<template>
  <div class="space-y-10 font-inter">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">New Asset <span class="text-vilcom-blue">Inquiry</span></h1>
      <p class="text-sm text-gray-500 font-medium mt-1 uppercase tracking-widest leading-relaxed">Request high-performance hardware that currently exists outside the system inventory.</p>
    </div>

    <div class="bg-white p-12 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
      <!-- Background watermark -->
      <div class="absolute -right-20 -bottom-20 size-80 bg-blue-50 rounded-full opacity-50 blur-3xl"></div>
      
      <div class="relative z-10 max-w-3xl space-y-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          <div class="space-y-3">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Hardware Designation</label>
            <input 
              v-model="form.item_name"
              class="w-full bg-slate-50 border-none rounded-2xl py-5 px-8 text-sm font-black text-slate-800 focus:ring-2 focus:ring-vilcom-blue/20 transition-all placeholder:text-gray-300"
              placeholder="e.g. MacBook Pro M3 Max"
            >
          </div>
          
          <div class="space-y-3">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Intended User / Sector</label>
            <div class="w-full bg-slate-100/50 rounded-2xl py-5 px-8 text-sm font-bold text-slate-400 flex items-center gap-3">
               <User class="size-4" />
               {{ userName }} (Personal Assignment)
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-2">Operational Requirement / Description</label>
          <textarea 
            v-model="form.description"
            rows="6"
            class="w-full bg-slate-50 border-none rounded-[2.5rem] py-8 px-10 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-vilcom-blue/20 transition-all placeholder:text-gray-300 leading-relaxed"
            placeholder="Detailed justification for this acquisition request. Include specifications if known..."
          ></textarea>
        </div>

        <div class="pt-6">
          <button 
            @click="submitRequest" 
            :disabled="loading"
            class="group relative overflow-hidden bg-vilcom-blue text-white px-12 py-5 rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/30 active:scale-95 transition-all disabled:opacity-50"
          >
            <div class="relative z-10 flex items-center gap-4">
              <ShoppingCart v-if="!loading" class="size-4 group-hover:rotate-12 transition-transform" />
              <div v-else class="size-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
              {{ loading ? 'Transmitting...' : 'Initialize Acquisition Protocol' }}
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Info Card -->
    <div class="bg-slate-900 p-10 rounded-[3rem] text-white flex flex-col md:flex-row items-center gap-10 shadow-2xl">
       <div class="p-6 bg-vilcom-orange rounded-3xl shadow-[0_0_30px_rgba(249,115,22,0.4)]">
         <ShieldCheck class="size-10" />
       </div>
       <div class="flex-1">
          <h4 class="text-xl font-black mb-2 tracking-tight">Management <span class="text-vilcom-orange">Review Chain</span></h4>
          <p class="text-slate-400 text-sm leading-relaxed font-medium">Once submitted, your request enters the management acquisition queue. Authorized personnel will review the operational necessity and budget alignment before procurement begins.</p>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { ShoppingCart, User, ShieldCheck } from 'lucide-vue-next';

const router = useRouter();
const userName = ref('Awaiting Data...');
const loading = ref(false);

const form = ref({
  item_name: '',
  description: '',
  type: 'asset_request'
});

onMounted(() => {
  const userData = localStorage.getItem('user_data');
  if (userData) {
    const user = JSON.parse(userData);
    userName.value = user.name;
  }
});

const submitRequest = async () => {
  if (!form.value.item_name || !form.value.description) {
    alert('Deployment protocol incomplete: Name and Description required.');
    return;
  }

  loading.value = true;
  try {
    await axios.post('/api/purchase-requests', form.value);
    alert('Request successfully queued for Management Approval.');
    router.push({ name: 'user-workspace-hub' });
  } catch (err) {
    console.error('Request failed:', err);
    alert('Inquiry failed to transmit. Check subsystem status.');
  } finally {
    loading.value = false;
  }
};
</script>
