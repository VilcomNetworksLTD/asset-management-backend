<template>
  <div class="p-8 space-y-12">
    <PageHeader title="Initiate Asset" highlight="Return" />

    <div class="max-w-3xl mx-auto bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden group hover:shadow-2xl transition-all duration-500">
      <div class="bg-slate-800 p-10 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 size-40 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
        <h2 class="text-2xl font-black text-white tracking-tight relative z-10 flex items-center gap-3">
          <Undo2 class="size-8 text-vilcom-orange" />
          Standard Handover
        </h2>
        <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] mt-2 relative z-10">Return assets to central administration</p>
      </div>

      <form @submit.prevent="submitReturn" class="p-12 space-y-10 font-medium">
        <!-- Asset Selection -->
        <div class="space-y-4">
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Asset being returned</label>
          <div class="relative group/field">
            <select 
              v-model="form.asset_id" 
              class="w-full bg-slate-50 border-none rounded-2xl p-5 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
            >
              <option value="">-- No primary asset --</option>
              <option v-for="asset in (myAssets || []).filter(a => a)" :key="asset.id" :value="asset.id">
                {{ asset.model || asset.Asset_Name }}
              </option>
            </select>
            <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
          </div>
        </div>

        <!-- Extra Items Section -->
        <div class="bg-indigo-50/30 rounded-[2rem] p-8 space-y-6 border border-indigo-50/50">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 tracking-tight">Included Components & Details</h3>
            <div v-if="loadingExtras" class="flex items-center gap-2 text-[10px] font-bold text-indigo-600 animate-pulse uppercase tracking-widest">
               Syncing Inventory...
            </div>
          </div>

          <div v-if="!loadingExtras" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Components -->
            <div class="space-y-4">
              <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest flex items-center gap-2">
                <LayoutGrid class="size-3" />
                Components
              </div>
              <div v-if="components.length" class="space-y-2">
                <label v-for="c in (components || []).filter(item => item)" :key="c.id" class="flex items-center gap-4 bg-white p-4 rounded-xl border border-transparent hover:border-indigo-100 transition-all cursor-pointer group/item shadow-sm">
                  <input type="checkbox" :value="c.id" v-model="selectedComponents" class="size-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                  <span class="text-xs font-bold text-slate-700">{{ c.name }}</span>
                </label>
              </div>
              <div v-else class="text-[10px] text-gray-400 italic">No assigned components</div>
            </div>

            <!-- Accessories -->
            <div class="space-y-4">
              <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest flex items-center gap-2">
                <Keyboard class="size-3" />
                Accessories
              </div>
              <div v-if="accessories.length" class="space-y-2">
                <label v-for="a in (accessories || []).filter(item => item)" :key="a.id" class="flex items-center gap-4 bg-white p-4 rounded-xl border border-transparent hover:border-indigo-100 transition-all cursor-pointer group/item shadow-sm">
                  <input type="checkbox" :value="a.id" v-model="selectedAccessories" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                  <span class="text-xs font-bold text-slate-700">{{ a.name }}</span>
                </label>
              </div>
              <div v-else class="text-[10px] text-gray-400 italic">No assigned accessories</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          <!-- Condition -->
          <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Current Condition</label>
            <div class="relative group/field">
              <select 
                v-model="form.sender_condition" 
                class="w-full bg-slate-50 border-none rounded-2xl p-5 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
                required
              >
                <option value="good">Operational / Good</option>
                <option value="damaged">Minor Wear / Scratches</option>
                <option value="broken">Hardware Failure</option>
                <option value="lost">Lost / Stolen</option>
              </select>
              <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
            </div>
          </div>
          
           <!-- Missing items notes -->
           <div class="space-y-4">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Missing Items?</label>
             <input
              v-model="form.notes"
              class="w-full bg-slate-50 border-none rounded-2xl p-5 text-xs font-bold text-slate-800 placeholder:text-gray-300 transition-all hover:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
              placeholder="e.g. Missing charger, original box..."
            >
          </div>
        </div>

        <div class="space-y-4">
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Reason for Handover</label>
          <textarea
            v-model="form.reason"
            class="w-full bg-slate-50 border-none rounded-[2rem] p-8 text-sm font-bold text-slate-800 placeholder:text-gray-300 transition-all hover:bg-slate-100 focus:ring-2  focus:ring-vilcom-blue"
            rows="3"
            placeholder="Please provide justification for returning these items..."
            required
          ></textarea>
        </div>

        <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100 text-blue-800 text-[10px] font-black uppercase tracking-widest leading-relaxed">
          <strong>Protocol:</strong>
          Your accountability only ends once the Admin physically inspects and certifies the return in the system.
        </div>

        <button
          type="submit"
          :disabled="loading || !isSubmittable"
          class="w-full bg-vilcom-orange text-white font-black py-6 rounded-2xl shadow-xl shadow-orange-900/10 hover:shadow-2xl hover:opacity-90 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 active:scale-[0.98]"
        >
          <Loader v-if="loading" class="size-5" />
          <Undo2 v-else class="size-5" />
          {{ loading ? 'PROCESSING...' : 'CONFIRM RETURN REQUEST' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { Undo2, ChevronDown, LayoutGrid, Keyboard } from 'lucide-vue-next'
import Loader from '@/components/Loader.vue'
import PageHeader from '@/components/PageHeader.vue'

const router = useRouter()

const myAssets = ref([])
const components = ref([])
const accessories = ref([])
const licenses = ref([])
// Consumables removed per system update

const loadingExtras = ref(false)
const loading = ref(false)

const selectedComponents = ref([])
const selectedAccessories = ref([])
const selectedLicenses = ref([])

const form = ref({
  asset_id: '',
  sender_condition: 'good',
  notes: '',
  reason: ''
})

const isSubmittable = computed(() => {
  return !!form.value.asset_id ||
    selectedComponents.value.length > 0 ||
    selectedAccessories.value.length > 0 ||
    selectedLicenses.value.length > 0;
});

/* LOAD ASSETS + EXTRAS */
onMounted(async () => {
  try {
    const assetRes = await axios.get('/api/my-returnable-assets')
    myAssets.value = assetRes.data || []
    await loadExtras()
  } catch (e) {
    console.error("Failed to load assets", e)
  }
})

const loadExtras = async () => {
    loadingExtras.value = true;
    try {
        const { data } = await axios.get('/api/my-assigned-items');
        
        
        components.value = (data.components || []).filter(Boolean).map(c => ({ ...c, name: c.Component_Name || c.name }));
        accessories.value = (data.accessories || []).filter(Boolean).map(a => ({ ...a, name: a.Accessory_Name || a.name }));
        licenses.value = (data.licenses || []).filter(Boolean).map(l => ({ ...l, name: l.License_Name || l.name }));
        
    } catch (error) {
        console.error("Error loading extras", error);
    } finally {
        loadingExtras.value = false;
    }
};

/* SUBMIT RETURN */
const submitReturn = async () => {
  loading.value = true
  try {
    const extras = []
    extras.push(...selectedComponents.value.map(id => ({ type: 'component', id })))
    extras.push(...selectedAccessories.value.map(id => ({ type: 'accessory', id })))
    extras.push(...selectedLicenses.value.map(id => ({ type: 'license', id })))

    const payload = {
      type: 'return', 
      ...form.value,
      items: extras.length ? extras : undefined
    }

    await axios.post('/api/return-requests', payload)
    alert('Return initiated! Please hand over the device to the Admin office.')
    router.push({ name: 'dashboard-user' })
  } catch (err) {
    console.error('submit failed', err)
    const msg = err.response?.data?.message || 'Unable to submit return request.'
    alert(msg)
  } finally {
    loading.value = false
    selectedComponents.value = []
    selectedAccessories.value = []
    selectedLicenses.value = []
  }
}
</script>