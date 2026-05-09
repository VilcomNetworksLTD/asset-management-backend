<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { 
  ArrowLeft, Barcode, Tag, Edit3, UserPlus, 
  Check, X, Camera, Printer, Trash2, 
  ChevronDown, LayoutGrid, Info, History, ShieldCheck,
  Send, PackageCheck, AlertCircle
} from 'lucide-vue-next';

import Modal from '@/components/Modal.vue';
import PageHeader from '@/components/PageHeader.vue';
import Loader from '@/components/Loader.vue';

const route = useRoute();
const router = useRouter();
const assetId = route.params.id;

const asset = ref(null);
const loading = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const uploadingEvidence = ref(false);

/**
 * Enhanced Access Control logic:
 * 1. Full access if on the explicit Admin Route ('asset-detail').
 * 2. Full access if user is an HOD/Manager, created the asset, AND is viewing via Management context.
 * 3. Restricted View-Only for Staff, Management, and HODs viewing assigned assets (My Assets / Dept Assets).
 */
const isAdmin = computed(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  const isExplicitAdmin = route.name === 'asset-detail';
  
  const isHOD = userData.role?.toLowerCase() === 'hod' || userData.role?.toLowerCase() === 'manager';
  const isManagementContext = route.path.includes('/tasks') || route.path.includes('/manage') || route.path.includes('/inventory') || route.path.includes('/definitions');
  
  // Check if HOD created either the category OR the asset itself
  const createdCategory = asset.value?.category?.created_by === userData.id;
  const createdAsset = asset.value?.created_by === userData.id;
  
  const isHODManager = isHOD && (createdCategory || createdAsset) && isManagementContext;
  
  return isExplicitAdmin || isHODManager;
});

// Assignment logic
const showAssignModal = ref(false);
const usersForDropdown = ref([]);
const componentsForDropdown = ref([]);
const assignmentForm = reactive({
  receiver_id: null,
  selected_components: [],
  notes: '',
  direct: true, 
});

// Dropdown data for edit
const locations = ref([]);
const statuses = ref([]);

const form = reactive({
  Asset_Name: '',
  location_id: null,
  Status_ID: null,
  Price: '',
  Purchase_Date: '',
  custom_attributes: {},
});

const fetchAsset = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/assets/${assetId}`);
    asset.value = data;
  } catch (error) {
    console.error("Error fetching asset:", error);
    window.vnlNotify.error("Could not load asset details.");
    router.push({ name: 'assets-list' });
  } finally {
    loading.value = false;
  }
};

const fetchDropdowns = async () => {
  try {
    const [locRes, statRes] = await Promise.all([
      axios.get('/api/locations'),
      axios.get('/api/statuses'),
    ]);
    locations.value = locRes.data;
    statuses.value = statRes.data;
  } catch (error) {
    console.error("Error loading dropdowns", error);
  }
};

const enableEditMode = async () => {
  if (locations.value.length === 0) await fetchDropdowns();
  
  form.Asset_Name = asset.value.Asset_Name;
  form.location_id = asset.value.location_id;
  form.Status_ID = asset.value.Status_ID;
  form.Price = asset.value.Price;
  form.Purchase_Date = asset.value.Purchase_Date ? asset.value.Purchase_Date.split('T')[0] : '';
  
  const schemaFields = asset.value.category?.fields || [];
  const existingAttributes = asset.value.custom_attributes || {};
  
   form.custom_attributes = {};
   schemaFields.forEach(field => {
     form.custom_attributes[field.name] = existingAttributes[field.name] ?? '';
   });

  isEditing.value = true;
};

const cancelEdit = () => {
  isEditing.value = false;
};

const handleFieldFileUpload = (key, event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      form.custom_attributes[key] = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const openAssignModal = async () => {
  assignmentForm.receiver_id = null;
  assignmentForm.selected_components = [];
  assignmentForm.notes = '';
  showAssignModal.value = true;

  if (!isAdmin.value) return; // Extra safety

  try {
    const [userRes, compRes] = await Promise.all([
      axios.get('/api/users-list'),
      axios.get('/api/components/list', { params: { per_page: 100 } })
    ]);
    usersForDropdown.value = userRes.data;
    componentsForDropdown.value = (compRes.data.data || compRes.data || []).filter(c => c.remaining_qty > 0);
  } catch (error) {
    console.error("Failed to fetch dropdown data:", error);
  }
};

const submitAssignment = async () => {
  if (!assignmentForm.receiver_id) {
    window.vnlNotify.error("Please select a user to assign the asset to.");
    return;
  }

  saving.value = true;
  try {
    const payload = {
      asset_id: asset.value.id,
      receiver_id: assignmentForm.receiver_id,
      notes: assignmentForm.notes,
      direct: assignmentForm.direct,
      items: assignmentForm.selected_components.map(id => ({ type: 'component', id }))
    };

    await axios.post('/api/admin/assets/assign', payload);
    showAssignModal.value = false;
    window.vnlNotify.success(assignmentForm.direct ? 'Asset assigned successfully.' : 'Asset assignment initiated.');
    fetchAsset(); 
  } catch (error) {
    console.error("Error assigning asset:", error);
    window.vnlNotify.error(error.response?.data?.message || "Failed to assign asset.");
  } finally {
    saving.value = false;
  }
};

const handleEvidenceUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('image', file);

  uploadingEvidence.value = true;
  try {
    const { data } = await axios.post(`/api/assets/${assetId}/evidence`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    asset.value.evidence_image = data.path; 
    window.vnlNotify.success("Proof of tagging saved!");
  } catch (error) {
    console.error("Upload error:", error);
    window.vnlNotify.error("Failed to save image.");
  } finally {
    uploadingEvidence.value = false;
  }
};

/**
 * FIXED PRINT BARCODE LOGIC
 */
const printBarcode = async () => {
  const assetData = asset.value; 
  if (!assetData || !assetData.id) return;

  // Use the root-relative API path and the ID to avoid URL slash issues
  const baseUrl = window.location.origin;
  const barcodeImageUrl = `${baseUrl}/api/barcodes/${assetData.id}/image`;
  
  // Verify image before opening window
  try {
    const response = await fetch(barcodeImageUrl);
    if (!response.ok) throw new Error('Image 404');
  } catch (error) {
    window.vnlNotify.error('Barcode image could not be reached. Check your network or server route.');
    return;
  }
  
  const printWindow = window.open('', '_blank', 'width:600,height:450');
  if (!printWindow) {
    window.vnlNotify.error("Please allow pop-ups for printing.");
    return;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Label - ${assetData.barcode}</title>
        <style>
          body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
          .label-box { border: 2px solid #000; padding: 20px; text-align: center; width: 300px; border-radius: 12px; }
          img { width: 100%; height: auto; max-height: 90px; display: block; margin: 10px 0; mix-blend-multiply: multiply; }
          .barcode-id { font-family: monospace; font-size: 20px; font-weight: 900; color: #1e40af; }
          .vnl { font-size: 10px; font-weight: bold; color: #f97316; margin-top: 5px; letter-spacing: 2px; }
        </style>
      </head>
      <body>
        <div class="label-box">
          <img src="${barcodeImageUrl}" onload="window.isLoaded = true;" />
          <div class="barcode-id">${assetData.barcode}</div>
          <div class="vnl">VILCOM NETWORKS</div>
        </div>
        <script>
          const poll = setInterval(() => {
            if (window.isLoaded) {
              clearInterval(poll);
              window.print();
              setTimeout(() => { window.close(); }, 500);
            }
          }, 100);
        <\/script>
      </body>
    </html>
  `);

  printWindow.document.close();
};

const saveChanges = async () => {
  saving.value = true;
  
  // Sanitize numeric data before sending
  const payload = {
    ...form,
    Price: form.Price === '' ? null : Number(form.Price)
  };

  try {
    const { data } = await axios.put(`/api/assets/${assetId}`, payload);
    asset.value = data.asset || data; // Update local state with returned asset
    isEditing.value = false;
    window.vnlNotify.success("Asset updated successfully.");
  } catch (error) {
    window.vnlNotify.error("Failed to update asset.");
  } finally {
    saving.value = false;
  }
};

const hasCategoryFields = computed(() => {
  return asset.value?.category?.fields && asset.value.category.fields.length > 0;
});

onMounted(() => {
  fetchAsset();
});
</script>

<template>
  <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-10">
    <div class="flex justify-between items-center">
      <router-link :to="{ name: 'assets-list' }" class="text-xs font-black text-gray-400 uppercase tracking-widest hover:text-vilcom-blue transition-colors flex items-center gap-2 group">
        <div class="p-2 bg-gray-50 rounded-lg group-hover:bg-blue-50 transition-colors">
          <ArrowLeft class="size-4" />
        </div>
        Back to Inventory
      </router-link>
      
      <div v-if="asset" class="flex gap-4">
        <template v-if="!isEditing && isAdmin">
          <button @click="enableEditMode" class="bg-white border border-gray-100 text-slate-700 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-50 hover:shadow-lg transition-all flex items-center gap-2">
            <Edit3 class="size-4 text-vilcom-blue" /> Edit Asset Details
          </button>
          <button @click="openAssignModal" class="bg-vilcom-blue text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-xl hover:opacity-90 transition-all flex items-center gap-2 shadow-lg shadow-blue-900/10">
            <UserPlus class="size-4" /> {{ asset.user ? 'Give to someone else' : 'Assign to member' }}
          </button>
        </template>
        <template v-else-if="isAdmin">
           <button @click="cancelEdit" class="text-gray-400 font-black text-[10px] uppercase tracking-widest hover:text-red-500 transition-colors">Cancel Changes</button>
           <button @click="saveChanges" :disabled="saving" class="bg-vilcom-orange text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-xl transition-all shadow-lg shadow-orange-900/10 active:scale-95 disabled:opacity-50">
             {{ saving ? 'Synchronizing...' : 'Saves Assets' }}
           </button>
        </template>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader />
    </div>

    <div v-else-if="asset" :class="['grid grid-cols-1 gap-10', isAdmin ? 'lg:grid-cols-4' : '']">
      
      <div :class="[isAdmin ? 'lg:col-span-3' : 'lg:col-span-4', 'space-y-10']">
        <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden group hover:shadow-xl transition-all duration-500">
          <div class="p-6 md:p-10 border-b border-gray-50 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
              <div v-if="isAdmin" class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-blue-50 rounded-xl text-vilcom-blue">
                   <Barcode class="size-6" />
                </div>
                <span class="font-mono text-xs font-black text-gray-400 bg-gray-50 px-3 py-1 rounded-lg">
                   {{ asset.barcode }}
                </span>
              </div>
              <h1 class="text-4xl font-black text-slate-800 tracking-tight">{{ asset.Asset_Name }}</h1>
              <div class="flex items-center gap-6 mt-4">
                <div class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                  <Tag class="size-4 text-vilcom-orange" />
                  {{ asset.category?.name }}
                </div>
                <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest', asset.user ? 'bg-blue-50 text-vilcom-blue' : 'bg-green-50 text-green-600']">
                  {{ asset.status?.Status_Name || 'In Stock' }}
                </span>
              </div>
            </div>

            <div class="text-left md:text-right">
              <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Current Custodian</div>
              <div class="flex items-center md:justify-end gap-3">
                 <div class="font-black text-slate-800">{{ asset.user?.name || 'VNL Stock / Available' }}</div>
                 <div class="size-10 bg-blue-50 rounded-full flex items-center justify-center text-vilcom-blue font-black uppercase">
                   {{ asset.user?.name?.charAt(0) || 'S' }}
                 </div>
               </div>
             </div>
           </div>

           <div class="p-6 md:p-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-12">
             <div class="space-y-2">
               <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Operational Location</label>
               <div v-if="!isEditing" class="font-black text-slate-700 p-4">{{ asset.location_model?.name || 'Unassigned' }}</div>
               <select v-else v-model="form.location_id" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue appearance-none">
                 <option :value="null">Unassigned</option>
                 <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
               </select>
             </div>

             <div v-if="isAdmin" class="space-y-2">
               <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Ownership State</label>
               <div class="font-black text-slate-700 p-4 flex items-center gap-2">
                 <div :class="['size-2 rounded-full', asset.user ? 'bg-blue-500' : 'bg-green-500']"></div>
                 {{ asset.user?.name || 'In Stock (Central Inventory)' }}
               </div>
             </div>

            <div v-if="isAdmin" class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Acquisition Value</label>
              <div v-if="!isEditing" class="font-black text-vilcom-blue p-4 text-xl">KES {{ Number(asset.Price || 0).toLocaleString() }}</div>
              <input v-else v-model="form.Price" type="number" step="0.01" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" />
            </div>

            <div v-if="isAdmin" class="space-y-2">
              <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Purchase Date</label>
              <div v-if="!isEditing" class="font-black text-slate-700 p-4">
                {{ asset.Purchase_Date ? new Date(asset.Purchase_Date).toLocaleDateString() : 'Not set' }}
              </div>
              <input v-else v-model="form.Purchase_Date" type="date" class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" />
            </div>
           </div>
         </div>

         <div v-if="hasCategoryFields" class="bg-white rounded-[3rem] p-6 md:p-12 shadow-sm border border-gray-100 space-y-8">
            <div class="flex items-center gap-4">
               <div class="p-3 bg-slate-50 rounded-2xl">
                  <LayoutGrid class="size-6 text-slate-400" />
               </div>
               <div>
                 <h2 class="text-xl font-black text-slate-800 tracking-tight">Technical Specifications</h2>
                 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Dynamic attributes for {{ asset.category.name }}</p>
               </div>
            </div>

             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-10">
                 <div v-for="field in asset.category.fields" :key="field.name" class="space-y-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    {{ field.label }}
                    <span v-if="isAdmin && field.type" class="ml-2 text-vilcom-blue/30 lowercase">({{ field.type }})</span>
                  </label>
                  
                  <!-- Display Mode -->
                  <div v-if="!isEditing" :class="['font-black text-slate-700 bg-slate-50/50 p-4 rounded-xl', (field.type === 'ip' || field.type === 'ip_address') ? 'font-mono' : '']">
                    <img v-if="field.type === 'image' && asset.custom_attributes?.[field.name]" :src="asset.custom_attributes[field.name]" class="max-w-full h-auto rounded" />
                    <div v-else-if="field.type === 'checkbox'">
                      <span :class="asset.custom_attributes?.[field.name] ? 'text-green-600' : 'text-gray-400'">
                        {{ asset.custom_attributes?.[field.name] ? 'Yes' : 'No' }}
                      </span>
                    </div>
                    <span v-else>{{ asset.custom_attributes?.[field.name] || '-' }}</span>
                  </div>
                  
                  <!-- Edit Mode -->
                  <template v-else>
                    <!-- Text, Email, IP, MAC -->
                    <input 
                      v-if="!field.type || ['text', 'email', 'ip_address', 'mac_address'].includes(field.type)"
                      v-model="form.custom_attributes[field.name]" 
                      type="text"
                      class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" 
                      :placeholder="field.type === 'ip_address' ? '192.168.1.1' : (field.type === 'mac_address' ? 'AA:BB:CC:DD:EE:FF' : '')"
                    />
                    <!-- Number -->
                    <input 
                      v-else-if="field.type === 'number'"
                      v-model="form.custom_attributes[field.name]" 
                      type="number"
                      class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" 
                    />
                    <!-- Date -->
                    <input 
                      v-else-if="field.type === 'date'"
                      v-model="form.custom_attributes[field.name]" 
                      type="date"
                      class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" 
                    />
                    <!-- Textarea -->
                    <textarea 
                      v-else-if="field.type === 'textarea'"
                      v-model="form.custom_attributes[field.name]" 
                      rows="3"
                      class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" 
                    ></textarea>
                    <!-- Checkbox -->
                    <div v-else-if="field.type === 'checkbox'" class="mt-1">
                      <label class="inline-flex items-center">
                        <input 
                          v-model="form.custom_attributes[field.name]"
                          type="checkbox"
                          :value="1"
                          class="rounded border-gray-300 text-blue-600"
                        />
                        <span class="ml-2 text-sm text-gray-500">Yes</span>
                      </label>
                    </div>
                    <!-- Select -->
                    <select 
                      v-else-if="field.type === 'select'"
                      v-model="form.custom_attributes[field.name]" 
                      class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue"
                    >
                      <option value="" disabled>Select {{ field.label }}</option>
                      <option v-for="opt in (field.options || '').split(',').map(o => o.trim())" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <!-- Image/File -->
                    <div v-else-if="field.type === 'image' || field.type === 'file'">
                      <input 
                        type="file"
                        :accept="field.type === 'image' ? 'image/*' : '*'"
                        @change="(e) => handleFieldFileUpload(field.name, e)"
                        class="w-full bg-slate-50 border-none rounded-xl p-4 font-bold focus:ring-2 focus:ring-vilcom-blue" 
                      />
                      <input 
                        v-model="form.custom_attributes[field.name]"
                        type="hidden"
                      />
                      <img v-if="form.custom_attributes[field.name]?.startsWith('data:image')" :src="form.custom_attributes[field.name]" class="mt-2 max-w-full h-32 object-cover rounded" />
                    </div>
                  </template>
                </div>
             </div>
         </div>

         <div v-if="isAdmin" class="bg-white rounded-[3rem] p-6 md:p-12 shadow-sm border border-gray-100 space-y-8">
            <div class="flex items-center gap-4">
               <div class="p-3 bg-slate-50 rounded-2xl">
                  <History class="size-6 text-slate-400" />
               </div>
               <div>
                 <h2 class="text-xl font-black text-slate-800 tracking-tight">Audit Trail</h2>
                 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Chronological activity & ownership logs</p>
               </div>
            </div>

             <div class="space-y-4">
                <div v-for="log in asset?.activity_logs || []" :key="log.id" class="flex items-center justify-between bg-slate-50 p-6 rounded-2xl group/log hover:bg-slate-100 transition-colors">
                  <div class="flex items-center gap-6">
                     <div class="min-w-[140px] text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        {{ new Date(log.created_at).toLocaleDateString() }}
                     </div>
                     <div class="text-sm font-bold text-slate-700">
                        <span class="text-vilcom-blue mr-2">{{ log.user?.name || 'System' }}</span>
                        {{ log.action }}
                     </div>
                  </div>
                  <div class="p-2 bg-white rounded-lg opacity-0 group-hover/log:opacity-100 transition-opacity">
                     <Info class="size-4 text-gray-300" />
                  </div>
               </div>
            </div>
         </div>
       </div>

       <div v-if="isAdmin" class="space-y-10">
          <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 flex flex-col items-center text-center space-y-6">
             <div class="p-6 bg-slate-50 rounded-3xl w-full flex items-center justify-center">
                <img :src="`/api/barcodes/${asset.id}/image`" class="h-14 mix-blend-multiply" />
             </div>
             <div class="font-mono text-sm font-black text-slate-800 uppercase tracking-widest">{{ asset.barcode }}</div>
             <button @click="printBarcode" class="w-full bg-gray-50 text-slate-600 px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-vilcom-blue hover:text-white transition-all flex items-center justify-center gap-3">
                <Printer class="size-4" /> Generate Physical Label
             </button>
          </div>

          <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 space-y-6">
             <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-3">
               <Camera class="size-4 text-vilcom-orange" />
               Proof of Tagging
             </h3>
             
             <div v-if="asset.evidence_image" class="aspect-square rounded-[2rem] overflow-hidden border border-gray-100 relative group/img shadow-inner">
                <img :src="asset.evidence_image.startsWith('http') ? asset.evidence_image : '/storage/' + asset.evidence_image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center">
                   <span class="text-white font-black text-[10px] uppercase tracking-widest">Update Photo</span>
                </div>
             </div>
             
             <div class="relative">
               <input type="file" accept="image/*" class="hidden" id="evidence-upload" @change="handleEvidenceUpload" />
               <label for="evidence-upload" class="cursor-pointer border-2 border-dashed border-slate-100 p-8 rounded-[2rem] hover:bg-slate-50 hover:border-vilcom-blue transition-all flex flex-col items-center justify-center gap-3">
                  <div class="p-4 bg-slate-50 rounded-full text-slate-300">
                    <Loader v-if="uploadingEvidence" class="size-6" />
                    <Camera v-else class="size-6" />
                  </div>
                  <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    {{ uploadingEvidence ? 'UPDATING...' : 'CAPTURING EVIDENCE' }}
                  </span>
               </label>
             </div>
          </div>

          <div v-if="asset.supplier" class="bg-vilcom-blue rounded-[2.5rem] p-10 text-white space-y-6 shadow-xl shadow-blue-900/10">
             <div class="flex items-center gap-4">
                <div class="p-3 bg-white/10 rounded-2xl">
                   <ShieldCheck class="size-6" />
                </div>
                <h3 class="font-black tracking-tight text-lg">Supplier</h3>
             </div>
             <div class="space-y-1">
                <div class="text-xl font-black">{{ asset.supplier.Supplier_Name }}</div>
                <div class="text-blue-200 text-xs font-bold leading-relaxed">{{ asset.supplier.Email }}</div>
             </div>
          </div>
       </div>
     </div>

     <Modal :show="showAssignModal" @close="showAssignModal = false" title="Assign or Transfer Asset">
       <form @submit.prevent="submitAssignment" class="p-6 md:p-10 space-y-8 md:space-y-10">
         <div class="space-y-4">
           <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Select Receiver</label>
           <div class="relative group/field">
              <select 
               v-model="assignmentForm.receiver_id" 
               class="w-full bg-slate-50 border-none rounded-2xl p-6 text-slate-800 font-bold appearance-none transition-all group-hover/field:bg-slate-100 focus:ring-2 focus:ring-vilcom-blue"
               required
             >
               <option :value="null" disabled>Search by name / email...</option>
               <option v-for="user in usersForDropdown" :key="user.id" :value="user.id">
                 {{ user.name }} ({{ user.email }})
               </option>
             </select>
             <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 size-5 pointer-events-none" />
           </div>
         </div>

         <div class="space-y-4">
           <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] flex justify-between">
             Include Extra Parts
             <span class="text-vilcom-blue">{{ assignmentForm.selected_components.length }} selected</span>
           </label>
           <div class="bg-slate-50 rounded-[2rem] p-6 max-h-60 overflow-y-auto custom-scrollbar space-y-2 border border-slate-100">
             <label v-for="comp in componentsForDropdown" :key="comp.id" class="flex items-center gap-4 bg-white p-4 rounded-xl hover:bg-blue-50/50 cursor-pointer transition-colors group/comp">
               <input type="checkbox" :value="comp.id" v-model="assignmentForm.selected_components" class="size-5 rounded border-gray-200 text-vilcom-blue focus:ring-vilcom-blue" />
               <div class="flex-1">
                 <div class="text-xs font-black text-slate-700">{{ comp.name }}</div>
                 <div class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">Remaining in stock: {{ comp.remaining_qty }} units</div>
               </div>
               <PackageCheck class="size-4 text-gray-200 group-hover/comp:text-vilcom-blue transition-colors" />
             </label>
             <div v-if="componentsForDropdown.length === 0" class="p-12 text-center text-[10px] font-black text-gray-300 uppercase tracking-widest italic">
               No transferable components in stock
             </div>
           </div>
         </div>

         <div class="space-y-4">
           <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Notes or Instructions</label>
           <textarea 
             v-model="assignmentForm.notes" 
             class="w-full bg-slate-50 border-none rounded-2xl p-6 text-sm font-bold placeholder:text-gray-300 focus:ring-2 focus:ring-vilcom-blue transition-all" 
             rows="3" 
             placeholder="Technical notes or operational constraints..."
           ></textarea>
         </div>

         <div class="flex items-center gap-4 p-6 bg-slate-50 rounded-2xl border border-slate-100">
           <input type="checkbox" id="direct-assign" v-model="assignmentForm.direct" class="size-6 rounded border-gray-300 text-vilcom-blue focus:ring-vilcom-blue" />
           <div class="flex-1">
             <label for="direct-assign" class="text-xs font-black text-slate-800 uppercase tracking-wider block">Fast Track (Skip confirmation)</label>
             <p class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">Transfer instantly without waiting for approval</p>
           </div>
           <AlertCircle class="size-5 text-gray-300" />
         </div>

         <div class="flex gap-4">
            <button @click="showAssignModal = false" type="button" class="flex-1 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-500 transition-colors py-6">Cancel</button>
            <button type="submit" :disabled="saving" class="flex-[3] bg-vilcom-blue text-white py-6 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:shadow-2xl shadow-xl shadow-blue-900/10 transition-all flex items-center justify-center gap-3 active:scale-95">
              <Send class="size-4 rotate-[-45deg] mb-1" />
              {{ saving ? 'UPDATING...' : 'Confirm Assignment' }}
            </button>
         </div>
       </form>
     </Modal>
   </div>
</template>