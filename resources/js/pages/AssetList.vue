<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'

const rows = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const suppliers = ref([])
const statuses = ref([])
const users = ref([])

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0
})

const showForm = ref(false)
const editingId = ref(null)

const form = reactive({
  Asset_Name: '',
  Asset_Category: '',
  Serial_No: '',
  Supplier_ID: '',
  Purchase_Date: '',
  Status_ID: '',
  Price: '',
  depreciation_value: 0,
  current_value: 0,
  location: '',
  warranty_expiry: '',
  warranty_image: null,
  warranty_image_url: '',
  processor: '',
  memory: '',
  storage_type: '',
  storage_capacity: '',
  operating_system: '',
  mac_address: '',
  ip_address: '',
})

const showAssignForm = ref(false)
const assignForm = reactive({ item: null, user_id: '' })


const filters = reactive({
  search: '',
  category: '',
  per_page: 10
})

const categories = computed(() => [...new Set(rows.value.map(r => r.Asset_Category).filter(Boolean))])


watch(() => form.Price, (newPrice) => {
  const price = Number(newPrice) || 0
  const depreciation = price * 0.1
  form.depreciation_value = depreciation
  form.current_value = price - depreciation
})

const isFocused = useWindowFocus()
watch(isFocused, (focused) => {
  if (focused) {
    fetchRows(pagination.current_page)
  }
})


const resetForm = () => {
  editingId.value = null
  // Reset all string/number fields
  Object.keys(form).forEach(k => {
    form[k] = (typeof form[k] === 'number') ? 0 : ''
  })
  // Explicitly handle non-string types
  form.depreciation_value = 0
  form.current_value = 0
  form.warranty_image = null
  form.processor = ''
  form.memory = ''
  form.storage_type = ''
  form.storage_capacity = ''
  form.operating_system = ''
  form.mac_address = ''
  form.ip_address = ''
}

const openCreate = () => {
  resetForm()
  showForm.value = true
}
const handleFileChange = (event) => {
  const file = event.target.files[0];
  form.warranty_image = file || null;
};

const openEdit = (row) => {
  if (row.deleted_at) return 
  editingId.value = row.id
  Object.keys(form).forEach(k => {
    if (row.specs && row.specs[k] !== undefined) {
      form[k] = row.specs[k]
    } else {
      form[k] = row[k] ?? ''
    }
  })
  // Always reset file input when opening for edit
  form.warranty_image = null;
  showForm.value = true
}

const openAssign = (item) => {
  assignForm.item = item
  assignForm.user_id = ''
  showAssignForm.value = true
}

const submitAssignment = async () => {
  if (!assignForm.item || !assignForm.user_id) {
    return alert('Please select a user.')
  }
  saving.value = true
  try {
    await axios.post(`/api/assets/${assignForm.item.id}/assign`, { user_id: assignForm.user_id })
    showAssignForm.value = false
    await fetchRows(pagination.current_page)
  } catch (e) {
    alert('Failed to assign asset: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

const fetchDropdowns = async () => {
  const [sup, stat, usr] = await Promise.all([
    axios.get('/api/suppliers'),
    axios.get('/api/statuses'),
    axios.get('/api/users')
  ])
  suppliers.value = sup.data
  statuses.value = stat.data
  users.value = usr.data
}

const fetchRows = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/assets/list', {
      params: {
        search: filters.search || undefined,
        category: filters.category || undefined,
        per_page: filters.per_page,
        page
      }
    })
    rows.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData();

    // Append all form fields to the FormData object.
    // We must handle null/undefined values correctly for FormData.
    for (const key in form) {
      if (key !== 'warranty_image') {
        if (form[key] !== null && form[key] !== undefined) {
          formData.append(key, form[key]);
        }
      }
    }

    // Append the file only if it's selected.
    if (form.warranty_image) {
      formData.append('warranty_image', form.warranty_image);
    }

    const config = {
      headers: { 'Content-Type': 'multipart/form-data' }
    };

    if (editingId.value) {
      // For file uploads with a PUT/PATCH, we must use POST and spoof the method.
      formData.append('_method', 'PUT');
      await axios.post(`/api/assets/${editingId.value}`, formData, config);
    } else {
      await axios.post('/api/assets', formData, config);
    }

    showForm.value = false
    await fetchRows(pagination.current_page)
  } catch (err) {
    console.error(err)
    const message = err.response?.data?.message || 'An error occurred.'
    const errors = err.response?.data?.errors ? '\n' + Object.values(err.response.data.errors).join('\n') : ''
    alert(message + errors)
  } finally {
    saving.value = false
  }
}

const removeRow = async (id) => {
  if (!confirm('Delete this asset?')) return
  await axios.delete(`/api/assets/${id}`)
  await fetchRows(pagination.current_page)
}

onMounted(async () => {
  await fetchDropdowns()
  await fetchRows()
})
</script>

<template>
<div class="p-6">

  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-semibold text-gray-800">Assets</h1>
    <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2 shadow-sm transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Create New
    </button>
  </div>

  <div class="bg-white p-3 rounded shadow-sm mb-3 flex gap-2 flex-wrap items-center">
    <input
      v-model="filters.search"
      @keyup.enter="fetchRows(1)"
      class="border px-3 py-2 rounded text-sm focus:ring-2 focus:ring-blue-500 outline-none"
      placeholder="Search assets..."
    />

    <select v-model="filters.category" class="border px-3 py-2 rounded text-sm">
      <option value="">All categories</option>
      <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
    </select>

    <select v-model.number="filters.per_page" class="border px-3 py-2 rounded text-sm">
      <option :value="10">10 per page</option>
      <option :value="20">20 per page</option>
      <option :value="50">50 per page</option>
    </select>

    <button
      @click="fetchRows(1)"
      class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded text-sm transition-colors"
    >
      Apply
    </button>
  </div>

  <div v-if="showForm" class="bg-white p-4 rounded shadow mb-4 grid grid-cols-2 gap-3 border-t-4 border-blue-600">
    <input v-model="form.Asset_Name" class="border p-2 rounded" placeholder="Asset Name" />
    
    <select v-model="form.Asset_Category" class="border p-2 rounded">
      <option value="">Select Category</option>
      <option value="Laptop">Laptop</option>
      <option value="Desktop">Desktop</option>
      <option value="Monitor">Monitor</option>
    </select>

    <input v-model="form.Serial_No" class="border p-2 rounded" placeholder="Serial No" />

    <select v-model="form.location" class="border p-2 rounded">
      <option value="">Select Location</option>
      <option value="Eldoret Office">Eldoret Office</option>
      <option value="Nairobi Office">Nairobi Office</option>
      <option value="Mombasa Office">Mombasa Office</option>
      <option value="Kisumu Office">Kisumu Office</option>
    </select>

    <select v-model="form.Supplier_ID" class="border p-2 rounded">
      <option value="">Select Supplier</option>
      <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.Supplier_Name }}</option>
    </select>

    <select v-model="form.Status_ID" class="border p-2 rounded">
      <option value="">Select Status</option>
      <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.Status_Name }}</option>
    </select>

    <input v-model="form.Price" type="number" class="border p-2 rounded" placeholder="Enter Asset Price" />
    <input :value="form.depreciation_value" readonly class="border p-2 rounded bg-gray-100" placeholder="Depreciation" />
    <input :value="form.current_value" readonly class="border p-2 rounded bg-gray-100" placeholder="Current Value" />

    <div class="col-span-2 border-t pt-4 mt-2">
      <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">System Configuration</h3>
      <div class="grid grid-cols-2 gap-3">
        <input v-model="form.processor" class="border p-2 rounded" placeholder="Processor (e.g. Intel i7)" />
        <input v-model="form.memory" class="border p-2 rounded" placeholder="Memory (e.g. 16GB)" />
        <input v-model="form.storage_type" class="border p-2 rounded" placeholder="Storage Type (SSD/HDD)" />
        <input v-model="form.storage_capacity" class="border p-2 rounded" placeholder="Storage Capacity (e.g. 512GB)" />
        <input v-model="form.operating_system" class="border p-2 rounded" placeholder="Operating System" />
        <input v-model="form.ip_address" class="border p-2 rounded" placeholder="IP Address" />
        <input v-model="form.mac_address" class="border p-2 rounded" placeholder="MAC Address" />
      </div>
    </div>

    <div class="col-span-2 grid grid-cols-2 gap-3 border-t pt-4 mt-2">
      <div>
        <label class="block text-xs font-medium text-gray-600">Purchase Date</label>
        <input v-model="form.Purchase_Date" type="date" class="border p-2 rounded w-full" />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600">Warranty Expiry Date</label>
        <input v-model="form.warranty_expiry" type="date" class="border p-2 rounded w-full" />
      </div>
      <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600">Warranty Image (Take or Upload)</label>
        <div v-if="form.warranty_image_url && !form.warranty_image" class="mb-2 p-2 border rounded bg-gray-50 flex items-center gap-3">
            <img :src="form.warranty_image_url" class="h-16 w-16 object-cover rounded border bg-white" />
            <div class="text-xs text-gray-500">
                <p class="font-bold text-gray-700">Current Image Stored</p>
                <p>Upload new to replace</p>
            </div>
        </div>
        <input @change="handleFileChange" type="file" accept="image/*" capture="environment" class="border p-2 rounded w-full text-sm" />
      </div>
    </div>
    <div class="col-span-2 flex gap-2">
      <button @click="submitForm" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition-colors">{{ editingId ? 'Update' : 'Create' }}</button>
      <button @click="showForm = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded transition-colors">Cancel</button>
    </div>
  </div>

  <div v-if="showAssignForm" class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="relative p-5 border w-96 shadow-2xl rounded-lg bg-white">
      <div class="mt-3 text-center">
        <h3 class="text-lg font-bold text-gray-900 border-b pb-2">Assign Asset</h3>
        <p class="text-sm text-gray-600 mt-2 italic">{{ assignForm.item?.Asset_Name }}</p>
        <div class="mt-4 px-2 text-left">
          <label class="text-xs font-bold uppercase text-gray-500">Select User</label>
          <select v-model="assignForm.user_id" class="w-full border p-2 rounded mt-1 outline-none focus:ring-2 focus:ring-green-500">
            <option value="" disabled>Select a user</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
        <div class="flex flex-col gap-2 mt-6">
          <button @click="submitAssignment" :disabled="saving" class="px-4 py-2 bg-green-600 text-white font-medium rounded-md shadow-sm hover:bg-green-700 disabled:opacity-50 transition-colors">
            {{ saving ? 'Processing...' : 'Confirm Assignment' }}
          </button>
          <button @click="showAssignForm = false" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded shadow overflow-auto border">
    <table class="w-full text-sm border-collapse">
      <thead>
        <tr class="border-b bg-gray-50 text-[11px] uppercase text-gray-500 font-bold">
          <th class="p-3 text-left">Name</th>
          <th class="p-3 text-left">Category</th>
          <th class="p-3 text-left">Serial</th>
          <th class="p-3 text-left">Supplier</th>
          <th class="p-3 text-left">Location</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-left">Purchase Date</th>
          <th class="p-3 text-left">Price</th>
          <th class="p-3 text-left">Depreciation</th>
          <th class="p-3 text-left">Current Val</th>
          <th class="p-3 text-left">Warranty</th>
          <th class="p-3 text-center">Actions</th>
        </tr>
      </thead>

      <tbody class="divide-y">
        <tr v-if="loading">
          <td colspan="12" class="p-6 text-center text-gray-500 italic">Loading asset records...</td>
        </tr>

        <tr v-for="asset in rows" :key="asset.id" class="hover:bg-gray-50 transition-colors">
          <td @click="$router.push({ name: 'asset-detail', params: { id: asset.id } })" class="p-3 font-medium text-blue-600 hover:underline cursor-pointer">
            {{ asset.Asset_Name }}
          </td>
          <td class="p-3">{{ asset.Asset_Category }}</td>
          <td class="p-3 font-mono text-xs">{{ asset.Serial_No }}</td>
          <td class="p-3">{{ asset.supplier?.Supplier_Name }}</td>
          <td class="p-3">{{ asset.location }}</td>
          <td class="p-3">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border border-gray-200 bg-white shadow-sm">
                {{ asset.status?.Status_Name }}
            </span>
          </td>
          <td class="p-3">{{ asset.Purchase_Date ? new Date(asset.Purchase_Date).toLocaleDateString() : '---' }}</td>
          <td class="p-3">{{ asset.Price }}</td>
          <td class="p-3 text-gray-400">{{ asset.depreciation_value }}</td>
          <td class="p-3 font-bold text-gray-700">{{ asset.current_value }}</td>
          <td class="p-3">{{ asset.warranty_expiry ? new Date(asset.warranty_expiry).toLocaleDateString() : '---' }}</td>

          <td class="p-3">
            <div class="flex items-center justify-center gap-3">
              <button
                @click.stop="openAssign(asset)"
                class="text-green-600 hover:text-green-800 disabled:opacity-30 transition-colors" 
                :disabled="asset.deleted_at || asset.Employee_ID"
                title="Assign Asset"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
              </button>

              <button
                @click.stop="openEdit(asset)"
                class="text-blue-600 hover:text-blue-800 disabled:opacity-30 transition-colors" 
                :disabled="asset.deleted_at"
                title="Edit Asset"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              </button>

              <button
                @click.stop="removeRow(asset.id)"
                class="text-red-600 hover:text-red-800 disabled:opacity-30 transition-colors" 
                :disabled="asset.deleted_at"
                title="Delete Asset"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
              </button>

              <span v-if="asset.deleted_at" class="px-2 py-0.5 text-[9px] font-bold rounded bg-red-700 text-white uppercase">Deleted</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</div>
</template>