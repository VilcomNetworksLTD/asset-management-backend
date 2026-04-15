Here is the updated minimalist component with the asset images and assignment history section removed, keeping only the essential information.
```vue
<template>
  <div class="p-6 md:p-8 max-w-6xl mx-auto space-y-6 font-sans min-h-screen bg-white">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-5 pb-2 border-b border-gray-100">
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
          <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Department Oversight</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-light text-gray-900 tracking-tight">
          Personnel Inventory
        </h1>
        <p class="text-sm text-gray-400 mt-1">
          Monitor assets assigned to staff members
        </p>
      </div>

      <div class="flex items-center gap-4 bg-gray-50 px-5 py-3 rounded-lg">
        <Users class="size-4 text-blue-500" />
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Active Staff</p>
          <p class="text-2xl font-semibold text-gray-900 leading-none">{{ staffWithAssets.length }}</p>
        </div>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="flex flex-col sm:flex-row gap-3 justify-between items-center py-2">
      <div class="relative w-full sm:w-80">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300" />
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Search staff or assets..." 
          class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-sm focus:outline-none focus:border-blue-300 focus:ring-1 focus:ring-blue-200 transition-all"
        />
      </div>
      <div class="flex gap-2">
        <button 
          v-for="filter in filters" 
          :key="filter.value"
          @click="activeFilter = filter.value"
          :class="[
            'px-4 py-1.5 rounded-md text-xs font-medium transition-all',
            activeFilter === filter.value 
              ? 'bg-blue-500 text-white' 
              : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'
          ]"
        >
          {{ filter.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-32 space-y-4">
      <Loader class="size-6 text-blue-400 animate-spin" />
      <p class="text-xs text-gray-400 uppercase tracking-wide">Loading personnel data...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-50 border border-red-100 p-4 rounded-lg flex items-center gap-3">
      <AlertCircle class="text-red-400 size-5" />
      <p class="text-sm text-red-600">{{ error }}</p>
      <button @click="fetchDepartmentAssets" class="ml-auto text-xs text-red-500 hover:text-red-700 font-medium">
        Retry
      </button>
    </div>

    <!-- Main Content -->
    <div v-if="!loading && !error" class="space-y-3">
      <div v-for="staff in filteredStaff" :key="staff.id" 
           class="bg-white border border-gray-100 rounded-xl overflow-hidden transition-all duration-200 hover:border-blue-200 hover:shadow-sm">
        
        <!-- Staff Header -->
        <div @click="toggleStaff(staff.id)" 
             class="flex justify-between items-center p-5 cursor-pointer hover:bg-gray-50/50 transition-colors">
          
          <div class="flex items-center gap-4">
            <div class="size-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500">
              <UserCheck class="size-5" />
            </div>
            
            <div>
              <h2 class="text-base font-medium text-gray-900">{{ staff.name }}</h2>
              <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                  <Package class="size-3" />
                  {{ staff.assets?.length || 0 }} assets
                </span>
                <span class="text-xs text-gray-300">•</span>
                <span class="text-xs text-gray-400">
                  {{ formatTotalValue(staff.assets) }}
                </span>
              </div>
            </div>
          </div>
          
          <ChevronDown :class="[
            'size-5 text-gray-400 transition-transform duration-200',
            expandedStaff.includes(staff.id) ? 'rotate-180 text-blue-500' : ''
          ]" />
        </div>

        <!-- Assets Grid -->
        <Transition name="expand">
          <div v-show="expandedStaff.includes(staff.id)">
            <div class="border-t border-gray-100 bg-gray-50/30 p-5">
              <div v-if="staff.assets?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Asset Card - No Image -->
                <div v-for="asset in staff.assets" :key="asset.id"
                     @click="showAssetDetails(asset, staff)"
                     class="group bg-white rounded-lg border border-gray-100 p-4 hover:border-blue-200 hover:shadow-sm transition-all cursor-pointer">
                  
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors">
                        {{ asset.Asset_Name }}
                      </h3>
                      <p class="text-xs text-gray-400 font-mono mt-1">{{ asset.Serial_No || 'No SN' }}</p>
                    </div>
                    <div class="size-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0 ml-3">
                      <Package class="size-4" />
                    </div>
                  </div>
                  
                  <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-50">
                    <span class="text-xs font-medium text-gray-500">{{ asset.category?.name || asset.Asset_Category || 'Asset' }}</span>
                    <span class="text-xs font-medium text-blue-600">{{ formatMoney(asset.current_value) }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-else class="text-center py-12">
                <div class="size-12 mx-auto bg-gray-50 rounded-lg flex items-center justify-center mb-3">
                  <Package class="size-5 text-gray-300" />
                </div>
                <h3 class="text-sm font-medium text-gray-500">No assets assigned</h3>
                <p class="text-xs text-gray-400 mt-1">This staff member has no assets in custody</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
      
      <!-- Empty State for No Results -->
      <div v-if="filteredStaff.length === 0" class="text-center py-16">
        <div class="size-16 mx-auto bg-gray-50 rounded-xl flex items-center justify-center mb-4">
          <Users class="size-7 text-gray-300" />
        </div>
        <h3 class="text-base font-medium text-gray-500">No staff found</h3>
        <p class="text-sm text-gray-400 mt-1">Try adjusting your search or filter</p>
      </div>
    </div>

    <!-- Modal - Minimal without History -->
    <Transition name="modal">
      <div v-if="selectedAsset" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="selectedAsset = null">
        <div class="fixed inset-0 bg-black/20 backdrop-blur-sm" @click="selectedAsset = null"></div>
        
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-xl relative flex flex-col max-h-[85vh] z-10">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
              <p class="text-xs text-blue-500 uppercase tracking-wide">Asset Details</p>
              <p class="text-sm font-medium text-gray-900">{{ selectedAsset.Asset_Name }}</p>
            </div>
            <button @click="selectedAsset = null" class="p-1.5 rounded-md hover:bg-gray-100 transition-colors">
              <X class="size-4 text-gray-400" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="flex-grow overflow-y-auto p-6 space-y-6">
            <!-- Basic Info -->
            <div class="space-y-4">
              <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded-md">
                  {{ selectedAsset.Asset_Category || 'General' }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-gray-50 text-gray-600 text-xs rounded-md font-mono">
                  TAG: {{ selectedAsset.Asset_Tag || 'N/A' }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-gray-50 text-gray-600 text-xs rounded-md font-mono">
                  SN: {{ selectedAsset.Serial_No || 'N/A' }}
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Current User</label>
                  <p class="text-sm font-medium text-gray-900 mt-0.5">{{ selectedAsset.current_user || 'Unassigned' }}</p>
                </div>
                <div>
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Location</label>
                  <p class="text-sm font-medium text-gray-900 mt-0.5">{{ selectedAsset.location || 'Headquarters' }}</p>
                </div>
                <div>
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Purchase Price</label>
                  <p class="text-sm font-medium text-gray-900 mt-0.5">{{ formatMoney(selectedAsset.purchase_price) }}</p>
                </div>
                <div>
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Current Value</label>
                  <p class="text-sm font-semibold text-blue-600 mt-0.5">{{ formatMoney(selectedAsset.current_value) }}</p>
                </div>
                <div v-if="selectedAsset.purchase_date">
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Purchase Date</label>
                  <p class="text-sm font-medium text-gray-900 mt-0.5">{{ formatDate(selectedAsset.purchase_date) }}</p>
                </div>
                <div v-if="selectedAsset.warranty_expiry">
                  <label class="text-xs text-gray-400 uppercase tracking-wide">Warranty Expiry</label>
                  <p class="text-sm font-medium text-gray-900 mt-0.5">{{ formatDate(selectedAsset.warranty_expiry) }}</p>
                </div>
              </div>
            </div>

            <!-- Specs Section -->
            <div v-if="selectedAsset.specs && Object.keys(selectedAsset.specs).length" class="border-t border-gray-100 pt-5">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Specifications</h4>
              <div class="grid grid-cols-2 gap-3">
                <div v-if="selectedAsset.specs.processor" class="bg-gray-50 rounded-md p-3">
                  <label class="text-xs text-gray-400">Processor</label>
                  <p class="text-sm text-gray-700">{{ selectedAsset.specs.processor }}</p>
                </div>
                <div v-if="selectedAsset.specs.memory" class="bg-gray-50 rounded-md p-3">
                  <label class="text-xs text-gray-400">Memory</label>
                  <p class="text-sm text-gray-700">{{ selectedAsset.specs.memory }}</p>
                </div>
                <div v-if="selectedAsset.specs.storage" class="bg-gray-50 rounded-md p-3">
                  <label class="text-xs text-gray-400">Storage</label>
                  <p class="text-sm text-gray-700">{{ selectedAsset.specs.storage }}</p>
                </div>
                <div v-if="selectedAsset.specs.os" class="bg-gray-50 rounded-md p-3">
                  <label class="text-xs text-gray-400">Operating System</label>
                  <p class="text-sm text-gray-700">{{ selectedAsset.specs.os }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useSettings } from '../composables/useSettings';
import Loader from '@/components/Loader.vue';

import { 
  Users, UserCheck, ChevronDown, Package, 
  X, AlertCircle, Search 
} from 'lucide-vue-next';

const staffWithAssets = ref([]);
const loading = ref(true);
const error = ref(null);
const expandedStaff = ref([]);
const selectedAsset = ref(null);
const searchQuery = ref('');
const activeFilter = ref('all');

const { settings } = useSettings();

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Has Assets', value: 'hasAssets' },
  { label: 'No Assets', value: 'noAssets' }
];

const filteredStaff = computed(() => {
  let filtered = staffWithAssets.value;
  
  if (activeFilter.value === 'hasAssets') {
    filtered = filtered.filter(staff => staff.assets?.length > 0);
  } else if (activeFilter.value === 'noAssets') {
    filtered = filtered.filter(staff => !staff.assets?.length);
  }
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(staff => 
      staff.name.toLowerCase().includes(query) ||
      staff.assets?.some(asset => 
        asset.Asset_Name?.toLowerCase().includes(query) ||
        asset.Serial_No?.toLowerCase().includes(query)
      )
    );
  }
  
  return filtered;
});

const formatTotalValue = (assets) => {
  if (!assets || assets.length === 0) return '0';
  const total = assets.reduce((sum, asset) => sum + (parseFloat(asset.current_value) || 0), 0);
  return `KSH ${total.toLocaleString()}`;
};

function formatMoney(amount) {
  if (amount == null || amount === '') return '0';
  return `KSH ${Number(amount).toLocaleString()}`;
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const fetchDepartmentAssets = async () => {
  loading.value = true;
  error.value = null;
  try {
    const userData = localStorage.getItem('user_data');
    const userRole = userData ? JSON.parse(userData).role?.toLowerCase() : 'staff';
    const endpoint = userRole === 'manager' ? '/api/manager/staff-assets' : '/api/hod/staff-assets';
    const response = await axios.get(endpoint);
    staffWithAssets.value = response.data.staff || [];
    
    // Check if user has department assigned
    if (staffWithAssets.value.length === 0) {
      error.value = 'No staff found in your department. Make sure you have a department assigned.';
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.error || 'Failed to load assets.';
  } finally {
    loading.value = false;
  }
};

const toggleStaff = (staffId) => {
  const index = expandedStaff.value.indexOf(staffId);
  index > -1 ? expandedStaff.value.splice(index, 1) : expandedStaff.value.push(staffId);
};

const showAssetDetails = async (asset, staff) => {
  selectedAsset.value = {
    ...asset,
    current_user: staff.name,
  };

  try {
    const response = await axios.get(`/api/assets/${asset.id}`);
    selectedAsset.value = { ...selectedAsset.value, ...response.data, current_user: staff.name };
  } catch (err) {
    console.error("Error:", err);
  }
};

onMounted(fetchDepartmentAssets);
</script>

<style scoped>
.expand-enter-active,
.expand-leave-active {
  transition: all 0.2s ease;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.2s ease;
}
.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>
```