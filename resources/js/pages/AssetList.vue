<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useWindowFocus } from '@vueuse/core';
import Modal from '@/components/Modal.vue';
import PageHeader from '@/components/PageHeader.vue';
import AssetTable from '@/components/AssetTable.vue';

const assets = ref([]);
const categories = ref([]);
const locations = ref([]);
const suppliers = ref([]);
const showAddModal = ref(false);
const showBarcodePreviewModal = ref(false);
const assetForPreview = ref(null);
const loading = ref(true);

const isFocused = useWindowFocus();
const REFRESH_INTERVAL = 20000;

watch(isFocused, (focused) => {
  if (focused) {
    fetchAssets();
  }
});

setInterval(() => {
  fetchAssets();
}, REFRESH_INTERVAL);

const form = reactive({
  Asset_Name: '',
  category_id: null,
  location_id: null,
  Supplier_ID: null,
  Price: null,
  Purchase_Date: null,
  custom_attributes: {}, // Added custom_attributes
});

const selectedCategory = computed(() => {
  return categories.value.find(c => c.id === form.category_id);
});

const fetchAssets = async () => {
  loading.value = true;
  try {
    // Using the paginated 'list' endpoint
    const { data } = await axios.get('/api/assets/list');
    assets.value = data.data; // Assuming pagination
  } catch (error) {
    console.error("Error fetching assets:", error);
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  // Reset form to its initial state
  Object.assign(form, {
    Asset_Name: '',
    category_id: null,
    location_id: null,
    Supplier_ID: null,
    Price: null,
    Purchase_Date: null,
    custom_attributes: {}, // Reset custom_attributes
  });
  showAddModal.value = true;
};

const handleFieldFileUpload = (key, event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      form.custom_attributes[key] = {
        name: file.name,
        data: e.target.result
      };
    };
    reader.readAsDataURL(file);
  }
};

const printBarcode = async (asset) => {
  if (!asset || !asset.id) return;
  
  const barcodeImageUrl = `/api/barcodes/${asset.barcode || asset.id}/image`; // Your backend endpoint
  
  // Verify barcode image is accessible before opening print window
  try {
    const response = await fetch(barcodeImageUrl);
    if (!response.ok) {
      throw new Error('Barcode image not available');
    }
  } catch (error) {
    console.error('Barcode image fetch error:', error);
    alert('Failed to load barcode image. Please try again or contact support.');
    return;
  }
  
  const printWindow = window.open('', '_blank', 'width=600,height=400');

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Barcode</title>
        <style>
          body { display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; font-family: sans-serif; margin: 0; }
          .barcode-container { text-align: center; border: 1px solid #eee; padding: 20px; width: 300px; }
          img { width: 100%; height: auto; max-height: 100px; } /* Ensures image scales */
          .barcode-text { font-family: monospace; font-size: 1.2em; margin-top: 5px; }
          .asset-name { margin-top: 10px; font-size: 1em; font-weight: bold; }
        </style>
      </head>
      <body>
        <div class="barcode-container">
          <img src="${barcodeImageUrl}" alt="barcode" onerror="this.style.display='none'; document.getElementById('error-msg').style.display='block';" />
          <div id="error-msg" style="display:none; color:red; font-size:12px;">Barcode image failed to load</div>
          <div class="barcode-text">${asset.barcode}</div>
          <div class="asset-name">${asset.Asset_Name}</div>
        </div>
        <script>
          // Crucial: Wait for the image to load before printing
          window.onload = function() { 
            setTimeout(() => {
              window.print(); 
              window.close(); 
            }, 500); 
          }
        <\/script>
      </body>
    </html>
  `);
  printWindow.document.close();
};

const submitAsset = async () => {
  try {
    console.log("Payload:", form);
    // 1. Send the request
    const response = await axios.post('/api/assets', form);
    
    // 2. Extract the data (Laravel returns it in response.data)
    const createdAsset = response.data;
    
    // 3. Debugging: Log this to your browser console to verify 'id' and 'barcode' exist
    console.log("New Asset Created:", createdAsset);

    // 4. Set the preview data FIRST
    assetForPreview.value = createdAsset;
    
    // 5. Close the "Add" modal and Open the "Barcode" modal
    showAddModal.value = false;
    
    // Use a slight timeout to allow the DOM to reset if they share a backdrop
    setTimeout(() => {
        showBarcodePreviewModal.value = true;
    }, 100);

    // 6. Refresh the main table in the background
    fetchAssets(); 
    
  } catch (error) {
    console.log("FULL ERROR:", error);
    console.log("RESPONSE:", error.response);
    console.log("DATA:", error.response?.data);
  }
};

const handlePrintFromPreview = async () => {
  if (!assetForPreview.value || !assetForPreview.value.id) return;

  const asset = assetForPreview.value;
  const barcodeImageUrl = `/api/barcodes/${asset.id}/image`;
  
  // Verify barcode image is accessible before opening print window
  try {
    const response = await fetch(barcodeImageUrl);
    if (!response.ok) {
      throw new Error('Barcode image not available');
    }
  } catch (error) {
    console.error('Barcode image fetch error:', error);
    alert('Failed to load barcode image. Please try again or contact support.');
    return;
  }

  const printWindow = window.open('', '_blank', 'width=600,height=400');
  
  printWindow.document.write(`
    <html>
      <head>
        <title>Print Asset Barcode</title>
        <style>
          @page { size: auto; margin: 0mm; }
          body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            flex-direction: column; 
            font-family: 'Courier New', Courier, monospace; 
            margin: 0; 
          }
          .label-container { 
            text-align: center; 
            border: 1px solid #000; 
            padding: 10px; 
            width: 250px;
          }
          img { 
            width: 200px; 
            height: auto; 
            display: block; 
            margin: 0 auto; 
          }
          .barcode-value { 
            font-size: 14px; 
            font-weight: bold; 
            margin-top: 5px; 
          }
          .asset-name { 
            font-size: 12px; 
            text-transform: uppercase; 
            margin-top: 2px; 
          }
        </style>
      </head>
      <body>
        <div class="label-container">
          <img src="${barcodeImageUrl}" alt="Barcode" onerror="this.style.display='none'; document.getElementById('error-msg').style.display='block';" />
          <div id="error-msg" style="display:none; color:red; font-size:12px;">Barcode image failed to load</div>
          <div class="barcode-value">${asset.barcode}</div>
          <div class="asset-name">${asset.Asset_Name}</div>
        </div>
        <script>
          // The image MUST load before the print dialog opens
          window.onload = function() {
            setTimeout(() => {
              window.print();
              window.close();
            }, 500);
          };
        <\/script>
      </body>
    </html>
  `);

  printWindow.document.close();
  
  // Close the preview modal after printing starts
  showBarcodePreviewModal.value = false;
  assetForPreview.value = null;
};

// Fetch data for dropdowns on component mount
onMounted(async () => {
  fetchAssets();
  try {
    const [catRes, locRes, supRes] = await Promise.all([
      axios.get('/api/categories'),
      axios.get('/api/locations'),
      axios.get('/api/suppliers'),
    ]);
    categories.value = catRes.data;
    locations.value = locRes.data;
    suppliers.value = supRes.data;
  } catch (error) {
    console.error("Failed to load data for form dropdowns:", error);
  }
});
</script>

<template>
  <div class="space-y-10">
    <PageHeader title="Asset" highlight="Inventory" @button-click="openAddModal" button-text="Add Asset" />
    
    <div class="mt-4">
      <AssetTable :assets="assets" :loading="loading" />
    </div>

    <!-- Add Asset Modal -->
    <Modal :show="showAddModal" @close="showAddModal = false" title="Create New Asset (Core Details)">
      <form @submit.prevent="submitAsset" class="space-y-4 p-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Asset Name</label>
          <input v-model="form.Asset_Name" type="text" class="mt-1 block w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Category</label>
          <select v-model="form.category_id" class="mt-1 block w-full" required>
            <option :value="null" disabled>Select a category</option>
            <option v-for="cat in (categories || []).filter(c => c)" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Location</label>
          <select v-model="form.location_id" class="mt-1 block w-full">
            <option :value="null">(Optional) Select a location</option>
            <option v-for="loc in (locations || []).filter(l => l)" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Supplier</label>
          <select v-model="form.Supplier_ID" class="mt-1 block w-full" required>
            <option :value="null" disabled>Select a supplier</option>
            <option v-for="sup in (suppliers || []).filter(s => s)" :key="sup.id" :value="sup.id">{{ sup.Supplier_Name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
          <input v-model="form.Purchase_Date" type="date" class="mt-1 block w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Price</label>
          <input v-model="form.Price" type="number" step="0.01" class="mt-1 block w-full" />
        </div>

        <!-- Dynamic Categorized Fields (Restored) -->
        <div v-if="selectedCategory && selectedCategory.fields?.length" class="space-y-4 pt-4 border-t">
          <p class="text-xs font-bold uppercase text-gray-500 mb-2">{{ selectedCategory.name }} Specifications</p>
          <div v-for="field in selectedCategory.fields" :key="field.key">
            <label class="block text-sm font-medium text-gray-700">
              {{ field.label }}
              <span v-if="field.type" class="text-[10px] text-gray-400 font-normal ml-1 lowercase">({{ field.type }})</span>
            </label>
            
            <!-- Text Input -->
            <input 
              v-if="!field.type || ['text', 'email', 'ip_address', 'mac_address'].includes(field.type)"
              v-model="form.custom_attributes[field.key]" 
              type="text"
              class="mt-1 block w-full border rounded p-2" 
              :placeholder="field.type === 'ip_address' ? 'e.g. 192.168.1.1' : (field.type === 'mac_address' ? 'e.g. AA:BB:CC:DD:EE:FF' : 'Enter ' + field.label)"
              :required="field.required" 
            />
            
            <!-- Number Input -->
            <input 
              v-else-if="field.type === 'number'"
              v-model="form.custom_attributes[field.key]" 
              type="number"
              class="mt-1 block w-full border rounded p-2" 
              :placeholder="'Enter ' + field.label"
              :required="field.required" 
            />
            
            <!-- Date Input -->
            <input 
              v-else-if="field.type === 'date'"
              v-model="form.custom_attributes[field.key]" 
              type="date"
              class="mt-1 block w-full border rounded p-2" 
              :required="field.required" 
            />
            
            <!-- Textarea -->
            <textarea 
              v-else-if="field.type === 'textarea'"
              v-model="form.custom_attributes[field.key]" 
              rows="3"
              class="mt-1 block w-full border rounded p-2" 
              :placeholder="'Enter ' + field.label"
              :required="field.required" 
            ></textarea>
            
            <!-- Checkbox -->
            <div v-else-if="field.type === 'checkbox'" class="mt-1">
              <label class="inline-flex items-center">
                <input 
                  v-model="form.custom_attributes[field.key]"
                  type="checkbox"
                  :value="1"
                  class="rounded border-gray-300 text-blue-600"
                  :required="field.required" 
                />
                <span class="ml-2 text-sm text-gray-500">Yes</span>
              </label>
            </div>
            
            <!-- Select Dropdown -->
            <select 
              v-else-if="field.type === 'select'"
              v-model="form.custom_attributes[field.key]" 
              class="mt-1 block w-full border rounded p-2"
              :required="field.required"
            >
              <option value="" disabled>Select {{ field.label }}</option>
              <option v-for="opt in (field.options || '').split(',').map(o => o.trim())" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            
            <!-- Image Upload -->
            <div v-else-if="field.type === 'image'">
              <input 
                type="file"
                accept="image/*"
                @change="(e) => handleFieldFileUpload(field.key, e)"
                class="mt-1 block w-full border rounded p-2"
                :required="field.required" 
              />
              <input 
                v-model="form.custom_attributes[field.key]"
                type="hidden"
              />
              <p v-if="form.custom_attributes[field.key]?.name" class="mt-1 text-sm text-green-600">{{ form.custom_attributes[field.key].name }}</p>
            </div>
            
            <!-- File Upload -->
            <div v-else-if="field.type === 'file'">
              <input 
                type="file"
                @change="(e) => handleFieldFileUpload(field.key, e)"
                class="mt-1 block w-full border rounded p-2"
                :required="field.required" 
              />
              <input 
                v-model="form.custom_attributes[field.key]"
                type="hidden"
              />
              <p v-if="form.custom_attributes[field.key]?.name" class="mt-1 text-sm text-green-600">{{ form.custom_attributes[field.key].name }}</p>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4 pt-4 border-t">
          <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Create Asset</button>
        </div>
      </form>
    </Modal>

    <!-- Barcode Preview Modal -->
    <Modal :show="showBarcodePreviewModal" @close="showBarcodePreviewModal = false" title="Asset Created: Barcode Preview">
  <div v-if="assetForPreview" class="p-6 text-center">
    <div class="mb-4">
        <span class="text-green-600 font-bold"><i class="fa fa-check-circle"></i> Asset successfully created!</span>
    </div>
    
    <h3 class="text-lg font-medium mb-2 text-gray-800">{{ assetForPreview.Asset_Name }}</h3>
    
    <div class="flex justify-center items-center flex-col p-6 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
      <img :src="`/api/barcodes/${assetForPreview.id}/image`" class="h-20 mx-auto" alt="Barcode" />
      <div class="font-mono text-sm text-gray-600 mt-2 font-bold">{{ assetForPreview.barcode }}</div>
    </div>

    <div class="flex justify-end gap-3 pt-6">
      <button type="button" @click="showBarcodePreviewModal = false" class="px-4 py-2 rounded bg-gray-100 text-gray-600 hover:bg-gray-200">
        Close
      </button>
      <button type="button" @click="handlePrintFromPreview" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 flex items-center gap-2">
        <i class="fa fa-print"></i> Print Barcode
      </button>
    </div>
  </div>
</Modal>
  </div>
</template>