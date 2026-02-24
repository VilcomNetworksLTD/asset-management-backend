<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Workflow Hub</h1>
      <p class="text-sm text-gray-500">
        {{ isAdmin ? 'Manage equipment requests, returns, and final disposition in one place.' : 'Request equipment and return assigned assets from one place.' }}
      </p>
    </div>

    <div v-if="!isAdmin" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm border p-5">
        <h3 class="font-bold text-gray-800 mb-4">Request Equipment (General)</h3>
        <div class="space-y-3">
          <select v-model="requestForm.requested_category" class="w-full border rounded p-2 text-sm">
            <option value="" disabled>Select Category</option>
            <option>Laptop</option>
            <option>Desktop</option>
            <option>Monitor</option>
            <option>Printer</option>
            <option>Accessory</option>
            <option>Other</option>
          </select>
          <select v-model="requestForm.priority" class="w-full border rounded p-2 text-sm">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
          <textarea v-model="requestForm.description" rows="4" class="w-full border rounded p-2 text-sm" placeholder="Why do you need this equipment?"></textarea>
          <button @click="submitEquipmentRequest" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold">Submit Request</button>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-5">
        <h3 class="font-bold text-gray-800 mb-4">Return Asset to Admin</h3>
        <div class="space-y-3">
          <select v-model="returnForm.asset_id" class="w-full border rounded p-2 text-sm">
            <option value="" disabled>Select Assigned Asset</option>
            <option v-for="asset in myAssets" :key="asset.id" :value="asset.id">
              {{ asset.Asset_Name }} ({{ asset.Serial_No || 'No Serial' }})
            </option>
          </select>
          <input v-model="returnForm.condition" class="w-full border rounded p-2 text-sm" placeholder="Current condition (e.g. Good, Damaged)" />
          <textarea v-model="returnForm.reason" rows="4" class="w-full border rounded p-2 text-sm" placeholder="Reason for return"></textarea>
          <button @click="submitReturnRequest" class="bg-orange-600 text-white px-4 py-2 rounded text-sm font-bold">Submit Return</button>
        </div>
      </div>
    </div>

    <div v-else class="space-y-6">
      <div class="bg-white rounded-lg shadow-sm border p-5">
        <h3 class="font-bold text-gray-800 mb-4">Equipment Requests</h3>
        <div class="space-y-3">
          <div v-for="ticket in equipmentRequests" :key="ticket.id" class="border rounded p-3">
            <div class="text-sm font-semibold text-gray-800">#{{ ticket.id }} — {{ ticket.user?.name || 'User' }}</div>
            <div class="text-xs text-gray-500 whitespace-pre-line">{{ ticket.Description }}</div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <select v-model="assignForms[ticket.id]" class="border rounded p-1 text-xs">
                <option value="" disabled>Select asset</option>
                <option v-for="asset in assignOptions[ticket.id] || []" :key="asset.id" :value="asset.id">
                  {{ asset.Asset_Name }} ({{ asset.Serial_No || 'No Serial' }})
                </option>
              </select>
              <button @click="loadAssignOptions(ticket)" class="text-xs px-2 py-1 border rounded">Load Assets</button>
              <button @click="assignAsset(ticket.id)" class="text-xs px-2 py-1 bg-green-600 text-white rounded">Assign</button>
            </div>
          </div>
          <div v-if="equipmentRequests.length === 0" class="text-sm text-gray-400 italic">No equipment requests.</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-5">
        <h3 class="font-bold text-gray-800 mb-4">Return Requests</h3>
        <div class="space-y-3">
          <div v-for="ticket in returnRequests" :key="ticket.id" class="border rounded p-3">
            <div class="text-sm font-semibold text-gray-800">#{{ ticket.id }} — {{ ticket.user?.name || 'User' }}</div>
            <div class="text-xs text-gray-500 whitespace-pre-line">{{ ticket.Description }}</div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <select v-model="returnDisposition[ticket.id]" class="border rounded p-1 text-xs">
                <option value="store">Back to Store</option>
                <option value="maintenance">Send to Maintenance</option>
              </select>
              <input v-model="returnNotes[ticket.id]" class="border rounded p-1 text-xs" placeholder="Notes" />
              <button @click="processReturn(ticket.id)" class="text-xs px-2 py-1 bg-indigo-600 text-white rounded">Process</button>
            </div>
          </div>
          <div v-if="returnRequests.length === 0" class="text-sm text-gray-400 italic">No return requests.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const isAdmin = ref(false)
const myAssets = ref([])
const equipmentRequests = ref([])
const returnRequests = ref([])

const requestForm = ref({ requested_category: '', description: '', priority: 'medium' })
const returnForm = ref({ asset_id: '', condition: '', reason: '' })

const assignForms = ref({})
const assignOptions = ref({})
const returnDisposition = ref({})
const returnNotes = ref({})

const getRole = () => {
  try {
    return (JSON.parse(localStorage.getItem('user_data') || '{}').role || 'user').toLowerCase()
  } catch {
    return 'user'
  }
}

const extractRequestedCategory = (description = '') => {
  const line = String(description).split('\n').find((l) => l.toLowerCase().startsWith('request category:'))
  return line ? line.split(':').slice(1).join(':').trim() : ''
}

const loadMyAssets = async () => {
  const { data } = await axios.get('/api/workflow/my-assets')
  myAssets.value = data || []
}

const loadAdminQueues = async () => {
  const { data } = await axios.get('/api/workflow/queues')
  equipmentRequests.value = data?.equipment_requests || []
  returnRequests.value = data?.return_requests || []

  returnRequests.value.forEach((ticket) => {
    if (!returnDisposition.value[ticket.id]) returnDisposition.value[ticket.id] = 'store'
    if (!returnNotes.value[ticket.id]) returnNotes.value[ticket.id] = ''
  })
}

const submitEquipmentRequest = async () => {
  await axios.post('/api/tickets', requestForm.value)
  alert('Equipment request submitted.')
  requestForm.value = { requested_category: '', description: '', priority: 'medium' }
}

const submitReturnRequest = async () => {
  await axios.post('/api/workflow/returns', returnForm.value)
  alert('Return request submitted to admin.')
  returnForm.value = { asset_id: '', condition: '', reason: '' }
}

const loadAssignOptions = async (ticket) => {
  const category = extractRequestedCategory(ticket.Description)
  const { data } = await axios.get('/api/assets/list', { params: { category: category || undefined, per_page: 100 } })
  const rows = data?.data || []
  assignOptions.value[ticket.id] = rows.filter((a) => {
    const status = String(a?.status?.Status_Name || '').toLowerCase()
    return status.includes('ready') || status.includes('available')
  })
}

const assignAsset = async (ticketId) => {
  const assetId = assignForms.value[ticketId]
  if (!assetId) return alert('Please select an asset first.')

  await axios.post(`/api/tickets/${ticketId}/assign-asset`, {
    asset_id: Number(assetId),
    communication: 'Assigned from Workflow Hub'
  })

  alert('Asset assigned successfully.')
  await loadAdminQueues()
}

const processReturn = async (ticketId) => {
  await axios.post(`/api/workflow/returns/${ticketId}/process`, {
    disposition: returnDisposition.value[ticketId] || 'store',
    notes: returnNotes.value[ticketId] || null,
    maintenance_type: returnDisposition.value[ticketId] === 'maintenance' ? 'Post-return repair assessment' : null,
  })

  alert('Return processed successfully.')
  await loadAdminQueues()
}

onMounted(async () => {
  isAdmin.value = getRole() === 'admin'

  if (isAdmin.value) {
    await loadAdminQueues()
  } else {
    await loadMyAssets()
  }
})
</script>
