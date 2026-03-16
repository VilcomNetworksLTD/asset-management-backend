<template>
  <div class="p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      <div class="bg-orange-500 p-4 text-white font-bold">
        Request Recall / Reassign
      </div>

      <div class="p-6 space-y-4">

        <div>
          <label class="block text-sm font-bold text-gray-700">Select Asset</label>
          <select v-model="form.asset_id" class="w-full border rounded p-2 mt-1">
            <option value="">-- none --</option>
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.model }} ({{ asset.serial }})
            </option>
          </select>
        </div>

        <!-- extra item selections ALWAYS shown -->
        <div class="pt-4">
          <div class="font-semibold mb-2">Additionally involved items</div>

          <div v-if="loadingExtras" class="text-center text-gray-500 mb-2">
            Loading items…
          </div>

          <div v-else>

            <!-- COMPONENTS -->
            <div class="mb-2">
              <div class="text-sm font-bold">Components</div>

              <div v-if="components.length" class="grid grid-cols-2 gap-2 mt-1">
                <label v-for="c in components" :key="c.id" class="inline-flex items-center gap-2">
                  <input type="checkbox" :value="c.id" v-model="selectedComponents">
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
                  <input type="checkbox" :value="a.id" v-model="selectedAccessories">
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
                  <input type="checkbox" :value="l.id" v-model="selectedLicenses">
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
                  <input type="checkbox" :value="c.id" v-model="selectedConsumables">
                  {{ c.name || c.consumable_name }}
                </label>
              </div>

              <div v-else class="text-xs text-gray-500 italic">
                No consumables available
              </div>
            </div>

          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">Movement Type</label>

          <div class="flex gap-4 mt-2">
            <label class="flex items-center gap-2">
              <input type="radio" v-model="form.type" value="return">
              Direct Return to Office
            </label>

            <label class="flex items-center gap-2">
              <input type="radio" v-model="form.type" value="transfer">
              Transfer to Colleague
            </label>
          </div>
        </div>

        <div v-if="form.type === 'transfer'">
          <label class="block text-sm font-bold text-gray-700">
            Transfer To (Employee)
          </label>

          <select v-model="form.receiver_id" class="w-full border rounded p-2 mt-1">
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">
            Self-Reported Condition
          </label>

          <select v-model="form.sender_condition" class="w-full border rounded p-2 mt-1">
            <option value="good">Good / Working</option>
            <option value="damaged">Damaged / Needs Repair</option>
            <option value="lost">Lost / Stolen</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">
            Missing Items (comma separated)
          </label>

          <input
            v-model="form.missing_items_text"
            class="w-full border rounded p-2 mt-1"
            placeholder="e.g Charger, Mouse dongle"
          >
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">
            Issues Observed
          </label>

          <textarea
            v-model="form.issue_notes"
            class="w-full border rounded p-2 mt-1"
            rows="3"
            placeholder="e.g Battery drains fast, cracked hinge"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700">
            Additional Notes
          </label>

          <textarea
            v-model="form.notes"
            class="w-full border rounded p-2 mt-1"
            rows="2"
            placeholder="Any extra handover details"
          ></textarea>
        </div>

        <button
          @click="submitRequest"
          class="w-full bg-orange-600 text-white font-bold py-2 rounded mt-4"
        >
          Send Request
        </button>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const myAssets = ref([])
const users = ref([])

const components = ref([])
const accessories = ref([])
const licenses = ref([])
const consumables = ref([])

const selectedComponents = ref([])
const selectedAccessories = ref([])
const selectedLicenses = ref([])
const selectedConsumables = ref([])

const loadingExtras = ref(false)

const form = ref({
  asset_id: '',
  type: 'return',
  receiver_id: '',
  sender_condition: 'good',
  missing_items_text: '',
  issue_notes: '',
  notes: '',
})

// fetch assets and users
onMounted(async () => {
  const [assetRes, userRes] = await Promise.all([
    axios.get('/api/my-assets'),
    axios.get('/api/users-list'),
  ])
  myAssets.value = assetRes.data || []
  users.value = userRes.data
  // load all extras initially
  await loadExtras()
})

// watch asset_id changes
watch(() => form.value.asset_id, async (assetId) => {
  await loadExtras(assetId)
})

async function loadExtras(assetId = null) {

  loadingExtras.value = true

  // COMPONENTS
  let compRes = await axios.get('/api/my-components', {
    params: { asset_id: assetId }
  })
  components.value = compRes.data || []

  // fallback if no components for selected asset
  if (assetId && components.value.length === 0) {
    const fallback = await axios.get('/api/my-components')
    components.value = fallback.data || []
  }

  const [accRes, licRes, consRes] = await Promise.all([
    axios.get('/api/my-accessories', { params: { asset_id: assetId } }),
    axios.get('/api/my-licenses', { params: { asset_id: assetId } }),
    axios.get('/api/my-consumables', { params: { asset_id: assetId } }),
  ])

  accessories.value = accRes.data || []
  licenses.value = licRes.data || []
  consumables.value = consRes.data || []

  loadingExtras.value = false
}

const submitRequest = async () => {

  // <-- FIXED: send correct fields for backend -->
  const items = []

  items.push(...selectedComponents.value.map(id => ({
    type: 'component',
    id: Number(id)
  })))

  items.push(...selectedAccessories.value.map(id => ({
    type: 'accessory',
    id: Number(id)
  })))

  items.push(...selectedLicenses.value.map(id => ({
    type: 'license',
    id: Number(id)
  })))

  items.push(...selectedConsumables.value.map(id => ({
    type: 'consumable',
    id: Number(id)
  })))

  const payload = {
    asset_id: form.value.asset_id,
    type: form.value.type,
    receiver_id: form.value.type === 'transfer'
      ? form.value.receiver_id
      : null,
    sender_condition: form.value.sender_condition,
    missing_items: (form.value.missing_items_text || '')
      .split(',')
      .map(v => v.trim())
      .filter(Boolean),
    issue_notes: form.value.issue_notes || null,
    notes: form.value.notes || null,
    items: items
  }

  try {
    console.log("PAYLOAD SENT:", payload)
    await axios.post('/api/transfers/return', payload)
    alert("Request sent! Please bring the asset to the Admin for physical inspection.")
  } catch (err) {
    console.error('transfer submit failed', err)
    const msg =
      err.response?.data?.message ||
      'Unable to submit transfer/return request.'
    alert(msg)
    return
  }

  // reset form
  form.value = {
    asset_id: '',
    type: 'return',
    receiver_id: '',
    sender_condition: 'good',
    missing_items_text: '',
    issue_notes: '',
    notes: '',
  }

  selectedComponents.value = []
  selectedAccessories.value = []
  selectedLicenses.value = []
  selectedConsumables.value = []
}
</script>