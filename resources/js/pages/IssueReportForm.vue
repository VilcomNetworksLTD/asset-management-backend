<template>
  <div class="max-w-4xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 px-4">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Support <span class="text-vilcom-blue">Terminal</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Operational Assistance & Resource Provisioning
        </p>
      </div>
      
      <button @click="router.push('/dashboard/user')" class="p-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-slate-100 transition-all">
        <X class="size-5" />
      </button>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-[3.5rem] shadow-sm border border-gray-100 overflow-hidden relative">
      <div class="p-12 space-y-12">
        <div class="flex items-center gap-5">
          <div class="p-4 bg-vilcom-blue text-white rounded-[1.5rem] shadow-xl shadow-blue-900/20">
            <MessageSquare class="size-6" />
          </div>
          <div>
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Submit Request</h3>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Ticketing & Procurement Queue</p>
          </div>
        </div>

        <form @submit.prevent="submitTicket" class="space-y-10">
          <!-- Request Type Selection -->
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Engagement Protocol</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="cursor-pointer group relative">
                <input type="radio" value="equipment_request" v-model="form.type" class="sr-only peer" />
                <div class="p-6 bg-slate-50 border-none rounded-3xl ring-1 ring-gray-100 peer-checked:ring-2 peer-checked:ring-vilcom-blue peer-checked:bg-blue-50/50 transition-all group-hover:bg-slate-100/50">
                  <div class="flex items-center gap-4">
                    <div class="size-10 rounded-xl bg-white flex items-center justify-center text-slate-400 peer-checked:text-vilcom-blue shadow-sm">
                      <ClipboardList class="size-5" />
                    </div>
                    <div>
                      <div class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Resource Request</div>
                      <div class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">Procurement / Acquisition</div>
                    </div>
                  </div>
                </div>
              </label>

              <label class="cursor-pointer group relative">
                <input type="radio" value="issue_report" v-model="form.type" class="sr-only peer" />
                <div class="p-6 bg-slate-50 border-none rounded-3xl ring-1 ring-gray-100 peer-checked:ring-2 peer-checked:ring-vilcom-orange peer-checked:bg-orange-50/50 transition-all group-hover:bg-slate-100/50">
                  <div class="flex items-center gap-4">
                    <div class="size-10 rounded-xl bg-white flex items-center justify-center text-slate-400 peer-checked:text-vilcom-orange shadow-sm">
                      <AlertTriangle class="size-5" />
                    </div>
                    <div>
                      <div class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Technical Issue</div>
                      <div class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">Report System Malfunction</div>
                    </div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Dynamic Fields -->
            <div v-if="form.type === 'equipment_request'" class="space-y-3">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Resource Category</label>
              <select v-model="form.requested_category" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none transition-all">
                <option value="" disabled>Baseline Category</option>
                <option value="Laptop">Computational Unit (Laptop)</option>
                <option value="Desktop CPU">Desktop Node (CPU)</option>
                <option value="Monitor">Visual Interface (Monitor)</option>
                <option value="Printer">Output Device (Printer)</option>
                <option value="Accessory">Peripheral / Accessory</option>
                <option value="Other">Unlisted Specification</option>
              </select>
            </div>

            <div v-if="form.type === 'equipment_request' && form.requested_category === 'Other'" class="space-y-3">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Specification Name</label>
              <input v-model="form.other_asset_name" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="Enter precise equipment name">
            </div>

            <div v-if="form.type === 'issue_report'" class="space-y-3">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Affected Infrastructure</label>
              <select v-model="form.asset_id" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-orange appearance-none transition-all">
                <option value="" disabled>Select Compromised Unit</option>
                <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
                  {{ asset.model }} [{{ asset.serial || 'NO_SN' }}]
                </option>
              </select>
            </div>

            <div class="space-y-3">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Priority Matrix</label>
              <select v-model="form.priority" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none transition-all">
                <option value="low">Low (Standard Maintenance)</option>
                <option value="medium">Medium (Operational Friction)</option>
                <option value="high">High (Critical Blockage)</option>
              </select>
            </div>
          </div>

          <!-- Description Field -->
          <div class="space-y-3">
            <div class="flex items-center justify-between ml-1">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Incident / Requirement Profile</label>
              <HelpCircle class="size-4 text-slate-200" />
            </div>
            <div class="relative">
              <textarea v-model="form.description" rows="5" class="w-full bg-slate-50 border-none rounded-[2rem] p-8 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" :placeholder="form.type === 'equipment_request' ? 'Outline operational justification and precise specifications...' : 'Detail exact symptoms and steps to reproduce the failure...'"></textarea>
              <div class="absolute bottom-6 right-8 text-[9px] font-black text-gray-300 uppercase tracking-widest pointer-events-none">
                Comprehensive Log Required
              </div>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
              <Info class="size-3" />
              Information provided is synced with Central IT Command
            </p>
          </div>

          <!-- Actions -->
          <div class="flex flex-col md:flex-row gap-6 pt-6">
            <button type="submit" :disabled="submitting" class="flex-[2] bg-vilcom-blue text-white py-6 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/30 hover:bg-blue-700 disabled:opacity-30 transition-all active:scale-95 flex items-center justify-center gap-3">
              {{ submitting ? 'Transmitting Data...' : (form.type === 'equipment_request' ? 'Transmit Procurement Request' : 'File Incident Report') }}
              <Send v-if="!submitting" class="size-4" />
            </button>
            <button type="button" @click="router.push('/dashboard/user')" class="flex-1 bg-slate-50 text-slate-400 py-6 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-100 transition-all">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { Laptop, MessageSquare, AlertTriangle, Send, X, ClipboardList, Info, HelpCircle } from 'lucide-vue-next';


const router = useRouter();
const myAssets = ref([]);
const form = ref({
  type: 'equipment_request',
  requested_category: '',
  other_asset_name: '',
  asset_id: '',
  description: '',
  priority: 'medium'
});

const submitting = ref(false);

onMounted(async () => {
  try {
    const res = await axios.get('/api/user-stats');
    myAssets.value = res.data.recent_assets || [];
  } catch (error) {
    console.error('Failed to load user assets', error);
  }
});

const submitTicket = async () => {
  if (submitting.value) return;
  submitting.value = true;

  const payload = {
    description: form.value.description,
    priority: form.value.priority
  };

   if (form.value.type === 'equipment_request') {
     payload.requested_category = form.value.requested_category;
     if (form.value.requested_category === 'Other') {
       if (!form.value.other_asset_name?.trim()) {
         alert('Please specify the equipment you need.');
         submitting.value = false;
         return;
       }
       payload.other_asset = form.value.other_asset_name.trim();
     }
   } else {
     payload.asset_id = form.value.asset_id;
   }

  try {
    await axios.post('/api/tickets', payload);
    alert(form.value.type === 'equipment_request' ? 'Request submitted successfully!' : 'Ticket submitted successfully!');
    router.push('/dashboard/user/my-tickets');
  } catch (e) {
    console.error('ticket submission failed', e);
    alert('There was an error submitting the ticket, please try again.');
  } finally {
    submitting.value = false;
  }
};
</script>