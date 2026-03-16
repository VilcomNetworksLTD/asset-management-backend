```vue
<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-indigo-700 p-4 text-white font-bold text-lg">
        <i class="fa fa-undo mr-2"></i> Initiate Asset Return
      </div>

      <form @submit.prevent="submitReturn" class="p-6 space-y-4">

        <!-- ASSET SELECT -->
        <div>
          <label class="block text-sm font-bold text-gray-700">Which asset are you returning?</label>
          <select v-model="form.asset_id" class="w-full border rounded p-2 mt-1">
            <option value="">-- none --</option>
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.model }} ({{ asset.serial }})
            </option>
          </select>
        </div>

        <!-- EXTRA ITEMS -->
        <div class="pt-4">
          <div class="font-semibold mb-2">Also included in this return?</div>

          <div v-if="loadingExtras" class="text-center text-gray-500 mb-2">
            Loading items…
          </div>

          <div v-else>

            <!-- COMPONENTS -->
            <div class="mb-2">
              <div class="text-sm font-bold">Components</div>

              <div v-if="components.length" class="grid grid-cols-2 gap-2 mt-1">
                <label v-for="c in components" :key="c.id" class="inline-flex items-center gap-2">
                  <input type="checkbox" :value="c.id" v-model="selectedComponents" />
                  {{ c.name || c.component_name }}
                </label>
              </div>

              <div v-else class="text-xs text-gray-500 italic">
                No components available
              </div>
            </div>

            <!-- ACCESSORIES -->
            <div class="mb-2">
              <div class="text-sm font-bold">Accessories</div>

              <div v-if="accessories.length" class="grid grid-cols-2 gap-2 mt-1">
                <label v-for="a in accessories" :key="a.id" class="inline-flex items-center gap-2">
                  <input type="checkbox" :value="a.id" v-model="selectedAccessories" />
                  {{ a.name || a.accessory_name }}
                </label>
              </div>

              <div v-else class="text-xs text-gray-500 italic">
                No accessories available
              </div>
            </div>

            <!-- LICENSES -->
            <div class="mb-2">
              <div class="text-sm font-bold">Licenses</div>

              <div v-if="licenses.length" class="grid grid-cols-2 gap-2 mt-1">
                <label v-for="l in licenses" :key="l.id" class="inline-flex items-center gap-2">
                  <input type="checkbox" :value="l.id" v-model="selectedLicenses" />
                  {{ l.name || l.license_name }}
                </label>
              </div>

              <div v-else class="text-xs text-gray-500 italic">
                No licenses available
              </div>
            </div>

            <!-- CONSUMABLES -->
            <div class="mb-2">
              <div class="text-sm font-bold">Consumables</div>

              <div v-if="consumables.length" class="grid grid-cols-2 gap-2 mt-1">
                <label v-for="c in consumables" :key="c.id" class="inline-flex items-center gap-2">
                  <input type="checkbox" :value="c.id" v-model="selectedConsumables" />
                  {{ c.name || c.consumable_name }}
                </label>
              </div>

              <div v-else class="text-xs text-gray-500 italic">
                No consumables available
              </div>
            </div>

          </div>
        </div>

        <!-- CONDITION -->
        <div>
          <label class="block text-sm font-bold text-gray-700">
            Current Condition (Self-Assessment)
          </label>

          <select v-model="form.sender_condition" class="w-full border rounded p-2 mt-1" required>
            <option value="good">Working / Good</option>
            <option value="damaged">Minor Damage (Scratches/Dents)</option>
            <option value="broken">Broken / Non-Functional</option>
            <option value="lost">Lost / Stolen</option>
          </select>
        </div>

        <!-- NOTES -->
        <div>
          <label class="block text-sm font-bold text-gray-700">
            Any missing peripherals?
          </label>

          <textarea
            v-model="form.notes"
            class="w-full border rounded p-2 mt-1"
            placeholder="e.g. Returned without charger..."
          ></textarea>
        </div>

        <!-- INFO BOX -->
        <div class="bg-blue-50 p-4 rounded text-blue-700 text-sm">
          <strong>Note:</strong>
          Your responsibility for this asset only ends once the Admin physically
          inspects and accepts it.
        </div>

        <!-- SUBMIT -->
        <button
          type="submit"
          :disabled="loading || !isSubmittable"
          class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="inline-flex items-center">
            <Loader class="w-4 h-4 mr-2" /> Processing...
          </span>

          <span v-else>
            Submit Return Request
          </span>
        </button>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import Loader from '@/components/Loader.vue'

const router = useRouter()

const myAssets = ref([])
const components = ref([])
const accessories = ref([])
const licenses = ref([])
const consumables = ref([])

const loadingExtras = ref(false)
const loading = ref(false)

const selectedComponents = ref([])
const selectedAccessories = ref([])
const selectedLicenses = ref([])
const selectedConsumables = ref([])

const form = ref({
  asset_id: '',
  sender_condition: 'good',
  notes: ''
})

const isSubmittable = computed(() => {
  return !!form.value.asset_id ||
    selectedComponents.value.length > 0 ||
    selectedAccessories.value.length > 0 ||
    selectedLicenses.value.length > 0 ||
    selectedConsumables.value.length > 0;
});

/* LOAD ASSETS + EXTRAS */
onMounted(async () => {

  const assetRes = await axios.get('/api/my-returnable-assets')
  myAssets.value = assetRes.data || []

  await loadExtras()

})

watch(() => form.value.asset_id, async (assetId) => {
  await loadExtras(assetId)
})

async function loadExtras(assetId = null) {

  loadingExtras.value = true

  try {

    let compRes = await axios.get('/api/my-components', {
      params: { asset_id: assetId }
    })

    components.value = compRes.data || []

    if (assetId && components.value.length === 0) {
      const fallback = await axios.get('/api/my-components')
      components.value = fallback.data || []
    }

    const [accRes, licRes, consRes] = await Promise.all([
      axios.get('/api/my-accessories', { params: { asset_id: assetId } }),
      axios.get('/api/my-licenses', { params: { asset_id: assetId } }),
      axios.get('/api/my-consumables', { params: { asset_id: assetId } })
    ])

    accessories.value = accRes.data || []
    licenses.value = licRes.data || []
    consumables.value = consRes.data || []

  } finally {

    loadingExtras.value = false

  }

}

/* SUBMIT RETURN */
const submitReturn = async () => {

  loading.value = true

  try {

    const extras = []

    extras.push(...selectedComponents.value.map(id => ({
      type: 'component',
      id
    })))

    extras.push(...selectedAccessories.value.map(id => ({
      type: 'accessory',
      id
    })))

    extras.push(...selectedLicenses.value.map(id => ({
      type: 'license',
      id
    })))

    extras.push(...selectedConsumables.value.map(id => ({
      type: 'consumable',
      id
    })))

    const payload = {
      type: 'return', // ⭐ REQUIRED FOR BACKEND VALIDATION
      ...form.value,
      items: extras.length ? extras : undefined
    }

    await axios.post('/api/return-requests', payload)

    alert('Return initiated! Please hand over the device to the Admin office.')

    router.push('/dashboard/user')

  } catch (err) {

    console.error('submit failed', err)

    const msg = err.response?.data?.message || 'Unable to submit return request.'

    alert(msg)

  } finally {

    loading.value = false

    selectedComponents.value = []
    selectedAccessories.value = []
    selectedLicenses.value = []
    selectedConsumables.value = []

  }

}
</script>
```
