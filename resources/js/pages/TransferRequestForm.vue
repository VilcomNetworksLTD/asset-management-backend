<template>
  <div class="p-8 space-y-12">
    <PageHeader title="Asset" highlight="Transfer Request" />

    <div class="max-w-3xl mx-auto bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden group hover:shadow-2xl transition-all duration-500">
      <div class="bg-vilcom-blue p-10 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 size-40 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <h2 class="text-2xl font-black text-white tracking-tight relative z-10">Colleague-to-Colleague Transfer</h2>
        <p class="text-blue-100/70 text-xs font-bold uppercase tracking-[0.2em] mt-2 relative z-10">Handover ownership to another staff member</p>
      </div>

      <div class="p-12 space-y-10">
        <!-- Asset Selection -->
        <div class="space-y-4">
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Select Primary Asset</label>
          <div class="relative group/field">
             <select 
              v-model="form.asset_id" 
              class="w-full bg-slate-50 border-none rounded-2xl p-5 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
            >
              <option value="">-- No primary asset --</option>
              <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
                {{ asset.model || asset.Asset_Name }} ({{ asset.serial || asset.Serial_No }})
              </option>
            </select>
            <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
          </div>
        </div>

        <!-- Extra Items Section -->
        <div class="bg-slate-50 rounded-[2rem] p-8 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 tracking-tight">Additionally Involved Items</h3>
            <div v-if="loadingExtras" class="flex items-center gap-2 text-[10px] font-bold text-vilcom-blue animate-pulse">
               <div class="size-2 bg-vilcom-blue rounded-full animate-bounce"></div>
               SYNCHRONIZING...
            </div>
          </div>

          <div v-if="!loadingExtras" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Components -->
            <div class="space-y-3">
              <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <div class="size-1.5 bg-indigo-400 rounded-full"></div>
                Components
              </div>
              <div v-if="components.length" class="space-y-2">
                <label v-for="c in components" :key="c.id" class="flex items-center gap-4 bg-white p-4 rounded-xl border border-transparent hover:border-indigo-100 hover:bg-indigo-50/50 transition-all cursor-pointer group/item">
                  <input type="checkbox" :value="c.id" v-model="selectedComponents" class="size-5 rounded border-gray-300 text-vilcom-blue focus:ring-vilcom-blue">
                  <span class="text-xs font-bold text-slate-700">{{ c.name }}</span>
                </label>
              </div>
              <div v-else class="text-[10px] text-gray-400 italic">No assigned components found</div>
            </div>

            <!-- Accessories -->
            <div class="space-y-3">
              <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <div class="size-1.5 bg-orange-400 rounded-full"></div>
                Accessories
              </div>
              <div v-if="accessories.length" class="space-y-2">
                <label v-for="a in accessories" :key="a.id" class="flex items-center gap-4 bg-white p-4 rounded-xl border border-transparent hover:border-orange-100 hover:bg-orange-50/50 transition-all cursor-pointer group/item">
                  <input type="checkbox" :value="a.id" v-model="selectedAccessories" class="size-5 rounded border-gray-300 text-vilcom-orange focus:ring-vilcom-orange">
                  <span class="text-xs font-bold text-slate-700">{{ a.name }}</span>
                </label>
              </div>
              <div v-else class="text-[10px] text-gray-400 italic">No assigned accessories found</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          <!-- Receiver Selection -->
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Transfer To (Employee)</label>
            <div class="relative group/field">
              <select 
                v-model="form.receiver_id" 
                class="w-full bg-slate-50 border-none rounded-2xl p-5 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
              >
                <option value="">-- Search employee --</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
              <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
            </div>
          </div>

          <!-- Condition -->
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Asset Condition</label>
            <div class="relative group/field">
              <select 
                v-model="form.sender_condition" 
                class="w-full bg-slate-50 border-none rounded-2xl p-5 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
              >
                <option value="good">Pristine / Good</option>
                <option value="damaged">Minor Operational Wear</option>
                <option value="broken">Hardware Damage</option>
              </select>
              <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div class="space-y-8">
            <div class="space-y-4">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Reason for Transfer</label>
                <textarea
                    v-model="form.reason"
                    class="w-full bg-slate-50 border-none rounded-2xl p-6 text-slate-800 font-bold placeholder:text-gray-300 transition-all hover:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
                    rows="3"
                    placeholder="Provide professional justification for this movement..."
                    required
                ></textarea>
            </div>

            <div class="space-y-4">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Handover Details / Issues</label>
                <textarea
                    v-model="form.issue_notes"
                    class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-bold text-slate-600 placeholder:text-gray-300 transition-all hover:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
                    rows="2"
                    placeholder="List any missing items (chargers, dongles) or specific issues the receiver should know..."
                ></textarea>
            </div>
        </div>

        <button
          @click="submitRequest"
          :disabled="submitting || !form.receiver_id || !form.reason"
          class="w-full bg-vilcom-blue text-white font-black py-6 rounded-2xl shadow-xl shadow-blue-900/10 hover:shadow-2xl hover:bg-blue-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 active:scale-[0.98]"
        >
          <Loader v-if="submitting" class="size-5" />
          <Send v-else class="size-5 rotate-[-45deg] mb-1" />
          {{ submitting ? 'PROCESSING TRANSACTION...' : 'INITIATE TRANSFER HANDOVER' }}
        </button>

        <p class="text-[10px] text-center text-gray-400 font-bold uppercase tracking-widest px-10 leading-loose">
          By submitting this request, you acknowledge the current state of assets and formalize the handover workflow.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { ChevronDown, Send } from 'lucide-vue-next'
import Loader from '@/components/Loader.vue'
import PageHeader from '@/components/PageHeader.vue'

const myAssets = ref([])
const users = ref([])

const components = ref([])
const accessories = ref([])
const licenses = ref([])
// Consumables removed per system update

const selectedComponents = ref([])
const selectedAccessories = ref([])
const selectedLicenses = ref([])

const loadingExtras = ref(false)
const submitting = ref(false)

const form = ref({
  asset_id: '',
  type: 'transfer', // Hardcoded to transfer only
  receiver_id: '',
  sender_condition: 'good',
  missing_items_text: '',
  issue_notes: '',
  notes: '',
  reason: '',
})

// fetch assets and users
onMounted(async () => {
  try {
    const [assetRes, userRes] = await Promise.all([
      axios.get('/api/my-assets'),
      axios.get('/api/users-list'),
    ])
    myAssets.value = assetRes.data || []
    users.value = userRes.data || []
    await loadExtras()
  } catch (err) {
    console.error("Initialization failed", err)
  }
})

const loadExtras = async () => {
    loadingExtras.value = true;
    try {
        const { data } = await axios.get('/api/my-assigned-items');
        
        components.value = (data.components || []).map(c => ({ ...c, type: 'component', name: c.Component_Name || c.name }));
        accessories.value = (data.accessories || []).map(a => ({ ...a, type: 'accessory', name: a.Accessory_Name || a.name }));
        licenses.value = (data.licenses || []).map(l => ({ ...l, type: 'license', name: l.License_Name || l.name }));
        
    } catch (err) {
        console.error("Extras load failed", err);
    } finally {
        loadingExtras.value = false;
    }
};

const submitRequest = async () => {
  submitting.value = true
  const items = []
  
  items.push(...selectedComponents.value.map(id => ({ type: 'component', id: Number(id) })))
  items.push(...selectedAccessories.value.map(id => ({ type: 'accessory', id: Number(id) })))
  items.push(...selectedLicenses.value.map(id => ({ type: 'license', id: Number(id) })))

  const payload = {
    asset_id: form.value.asset_id,
    type: 'transfer',
    receiver_id: form.value.receiver_id,
    sender_condition: form.value.sender_condition,
    missing_items: (form.value.missing_items_text || '')
      .split(',')
      .map(v => v.trim())
      .filter(Boolean),
    issue_notes: form.value.issue_notes || null,
    notes: form.value.notes || null,
    reason: form.value.reason || null,
    items: items
  }

  try {
    await axios.post('/api/request-transfer', payload)
    alert("Handover request sent! The colleague will receive a notification to verify the items.")
    
    // reset form on success
    form.value = {
      asset_id: '',
      type: 'transfer',
      receiver_id: '',
      sender_condition: 'good',
      missing_items_text: '',
      issue_notes: '',
      notes: '',
      reason: '',
    }
    selectedComponents.value = []
    selectedAccessories.value = []
    selectedLicenses.value = []

  } catch (err) {
    console.error('transfer submit failed', err)
    const msg = err.response?.data?.message || 'Unable to submit transfer request.'
    alert(msg)
  } finally {
    submitting.value = false
  }
}
</script>