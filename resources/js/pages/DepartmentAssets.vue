<script setup>
import { onMounted, ref, computed, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useWindowFocus } from '@vueuse/core'
import Loader from '@/components/Loader.vue'
import { Eye, Package, Users, UserCheck, ChevronDown, X, Search } from 'lucide-vue-next'

const isFocused = useWindowFocus()
const REFRESH_INTERVAL = 25000
let intervalId = null

const staffWithAssets = ref([])
const loading = ref(true)
const error = ref(null)
const departmentName = ref('')
const expandedStaff = ref([])
const selectedAsset = ref(null)
const searchQuery = ref('')
const activeFilter = ref('all')

const router = useRouter()

const goToDetail = (id) => {
  router.push({ name: 'user-asset-detail', params: { id } });
};

const fetchDepartmentAssets = async () => {
  loading.value = true
  error.value = null
  try {
    const userData = localStorage.getItem('user_data');
    const userRole = userData ? JSON.parse(userData).role?.toLowerCase() : 'staff';
    const endpoint = userRole === 'manager' ? '/api/manager/staff-assets' : '/api/hod/staff-assets';
    const response = await axios.get(endpoint)
    staffWithAssets.value = response.data.staff || []
    departmentName.value = response.data.department_name || 'Department'
    
    if (staffWithAssets.value.length === 0) {
      error.value = 'No staff found in your department. Make sure you have a department assigned.';
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.error || 'Failed to load assets.';
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDepartmentAssets()
  intervalId = setInterval(fetchDepartmentAssets, REFRESH_INTERVAL)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})

watch(isFocused, (focused) => {
  if (focused) fetchDepartmentAssets()
})

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Has Assets', value: 'hasAssets' },
  { label: 'No Assets', value: 'noAssets' }
]

const filteredStaff = computed(() => {
  let filtered = staffWithAssets.value
  
  if (activeFilter.value === 'hasAssets') {
    filtered = filtered.filter(staff => staff.assets?.length > 0)
  } else if (activeFilter.value === 'noAssets') {
    filtered = filtered.filter(staff => !staff.assets?.length)
  }
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(staff => 
      staff.name?.toLowerCase().includes(query) ||
      staff.assets?.some(asset => 
        asset.Asset_Name?.toLowerCase().includes(query) ||
        asset.Serial_No?.toLowerCase().includes(query)
      )
    )
  }
  
  return filtered
})

const formatTotalValue = (assets) => {
  if (!assets || assets.length === 0) return 'KSH 0'
  const total = assets.reduce((sum, asset) => sum + (parseFloat(asset.current_value) || 0), 0)
  return `KSH ${total.toLocaleString()}`
}

function formatMoney(amount) {
  if (amount == null || amount === '') return 'KSH 0'
  return `KSH ${Number(amount).toLocaleString()}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const toggleStaff = (staffId) => {
  const index = expandedStaff.value.indexOf(staffId)
  index > -1 ? expandedStaff.value.splice(index, 1) : expandedStaff.value.push(staffId)
}

const showAssetDetails = async (asset, staff) => {
  selectedAsset.value = {
    ...asset,
    current_user: staff.name,
  }
  try {
    const response = await axios.get(`/api/assets/${asset.id}`)
    selectedAsset.value = { ...selectedAsset.value, ...response.data, current_user: staff.name }
  } catch (err) {
    console.error("Error:", err)
  }
}

const components = { Loader }
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 p-6 md:p-8">
    <!-- Hero Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-8 md:p-10">
      <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIgZmlsbD0iI2ZmZiIgZmlsbC1vcGFjaXR5PSIwLjA1Ii8+PC9zdmc+')] opacity-30"></div>
      <div class="absolute top-0 right-0 w-64 h-64 bg-vilcom-orange/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-48 h-48 bg-vilcom-blue/20 rounded-full blur-3xl"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-2xl bg-vilcom-orange flex items-center justify-center">
              <Users class="text-white size-6" />
            </div>
            <span class="text-slate-400 font-bold text-sm uppercase tracking-widest">Personnel Overview</span>
          </div>
          <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight mb-2">
            {{ departmentName }} <span class="text-vilcom-orange">Staff</span>
          </h1>
          <p class="text-slate-400 text-sm font-medium">Monitor assets assigned to each staff member</p>
        </div>
        
        <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-4 border border-white/10">
          <div>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Total Staff</p>
            <p class="text-3xl font-black text-white">{{ staffWithAssets.length }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
      <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2 p-1 bg-slate-50 rounded-xl">
          <button
            v-for="filter in filters"
            :key="filter.value"
            @click="activeFilter = filter.value"
            :class="[
              'px-5 py-2.5 rounded-lg text-sm font-black uppercase tracking-wider transition-all',
              activeFilter === filter.value 
                ? 'bg-white text-vilcom-blue shadow-sm' 
                : 'text-slate-400 hover:text-slate-600'
            ]"
          >
            {{ filter.label }}
          </button>
        </div>
        
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 size-5" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search staff or assets..." 
            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:outline-none focus:border-vilcom-blue focus:ring-2 focus:ring-vilcom-blue/10 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-32 space-y-4">
      <Loader class="size-10 text-vilcom-blue" />
      <p class="text-slate-400 font-medium text-sm uppercase tracking-widest">Loading personnel data...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-50 border border-red-100 p-6 rounded-2xl flex items-center gap-4">
      <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
        <Users class="text-red-500 size-6" />
      </div>
      <div class="flex-1">
        <p class="text-sm font-bold text-red-600">{{ error }}</p>
      </div>
      <button @click="fetchDepartmentAssets" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-bold hover:bg-red-600 transition-all">
        Retry
      </button>
    </div>

    <!-- Main Content - Staff Cards -->
    <div v-if="!loading && !error" class="space-y-4">
      <div 
        v-for="staff in filteredStaff" 
        :key="staff.id" 
        class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg hover:border-vilcom-blue/30 transition-all duration-300 relative"
      >
        <!-- Colored left accent -->
        <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-2xl"
          :class="staff.assets?.length > 0 ? 'bg-gradient-to-b from-vilcom-blue to-vilcom-orange' : 'bg-slate-200'"
        ></div>
        
        <!-- Staff Header -->
        <div 
          @click="toggleStaff(staff.id)" 
          class="flex justify-between items-center p-6 pl-8 cursor-pointer hover:bg-slate-50/50 transition-colors"
        >
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-vilcom-blue to-vilcom-orange flex items-center justify-center shadow-lg">
              <UserCheck class="size-7 text-white" />
            </div>
            
            <div>
              <h2 class="text-lg font-black text-slate-800 tracking-tight">{{ staff.name }}</h2>
              <div class="flex items-center gap-3 mt-2">
                <span :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold',
                  staff.assets?.length > 0 
                    ? 'bg-vilcom-blue/10 text-vilcom-blue' 
                    : 'bg-slate-100 text-slate-400'
                ]">
                  <Package class="size-3" />
                  {{ staff.assets?.length || 0 }} assets
                </span>
                <span class="text-slate-300">•</span>
                <span class="text-sm font-black" :class="staff.assets?.length > 0 ? 'text-vilcom-blue' : 'text-slate-400'">
                  {{ formatTotalValue(staff.assets) }}
                </span>
              </div>
            </div>
          </div>
          
          <ChevronDown 
            :class="[
              'size-6 text-slate-300 transition-transform duration-300',
              expandedStaff.includes(staff.id) ? 'rotate-180 text-vilcom-blue' : ''
            ]" 
          />
        </div>

        <!-- Assets Grid -->
        <Transition name="expand">
          <div v-show="expandedStaff.includes(staff.id)">
            <div class="border-t border-slate-100 bg-gradient-to-br from-slate-50 to-blue-50/30 p-6 pl-8">
              <div v-if="staff.assets?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div 
                  v-for="asset in staff.assets" 
                  :key="asset.id"
                  @click="showAssetDetails(asset, staff)"
                  class="group bg-white rounded-xl border-2 border-slate-100 p-5 hover:border-vilcom-orange hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer relative overflow-hidden"
                >
                  <!-- Top gradient line -->
                  <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-vilcom-blue to-vilcom-orange"></div>
                  
                  <div class="flex items-start justify-between mb-4 pt-2">
                    <div class="flex-1">
                      <h3 class="text-sm font-bold text-slate-800 group-hover:text-vilcom-orange transition-colors line-clamp-2">
                        {{ asset.Asset_Name }}
                      </h3>
                      <p class="text-xs text-slate-400 font-mono mt-2">{{ asset.Serial_No || 'No SN' }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-vilcom-blue/10 to-vilcom-orange/10 flex items-center justify-center shrink-0 ml-3">
                      <Package class="text-vilcom-blue size-5" />
                    </div>
                  </div>
                  
                  <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                    <span class="px-2 py-1 rounded-md bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wide">{{ asset.category?.name || 'Asset' }}</span>
                    <span class="text-sm font-black text-vilcom-orange">{{ formatMoney(asset.current_value) }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-else class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-slate-100 to-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-4">
                  <Package class="size-10 text-slate-400" />
                </div>
                <h3 class="text-base font-black text-slate-500 mb-2">No assets assigned</h3>
                <p class="text-sm text-slate-400">This staff member has no assets in custody</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
      
      <!-- Empty State for No Results -->
      <div v-if="filteredStaff.length === 0" class="text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-slate-200 to-slate-300"></div>
        <div class="w-24 h-24 bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Users class="size-12 text-slate-300" />
        </div>
        <h3 class="text-xl font-black text-slate-500 mb-2">No staff found</h3>
        <p class="text-slate-400 font-medium">Try adjusting your search or filter</p>
      </div>
    </div>

    <!-- Asset Details Modal -->
    <Transition name="modal">
      <div v-if="selectedAsset" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="selectedAsset = null">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="selectedAsset = null"></div>
        
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl relative flex flex-col max-h-[85vh] z-10 overflow-hidden">
          <!-- Gradient Top -->
          <div class="h-2 bg-gradient-to-r from-vilcom-blue to-vilcom-orange"></div>
          
          <!-- Modal Header -->
          <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <div>
              <p class="text-xs font-bold text-vilcom-blue uppercase tracking-widest">Asset Details</p>
              <p class="text-lg font-black text-slate-800 mt-1">{{ selectedAsset.Asset_Name }}</p>
            </div>
            <button @click="selectedAsset = null" class="p-2 rounded-xl hover:bg-slate-100 transition-colors">
              <X class="size-5 text-slate-400" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="flex-grow overflow-y-auto p-6 space-y-6">
            <!-- Tags -->
            <div class="flex flex-wrap gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-vilcom-blue/10 text-vilcom-blue text-xs font-bold uppercase tracking-wider rounded-lg">
                {{ selectedAsset.category?.name || 'General' }}
              </span>
            </div>
            
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-50 rounded-xl p-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current User</label>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ selectedAsset.current_user || 'Unassigned' }}</p>
              </div>
              <div class="bg-slate-50 rounded-xl p-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Location</label>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ selectedAsset.location?.name || 'Headquarters' }}</p>
              </div>
              <div class="bg-vilcom-blue/10 rounded-xl p-4">
                <label class="text-[10px] font-bold text-vilcom-blue uppercase tracking-widest">Current Value</label>
                <p class="text-sm font-black text-vilcom-blue mt-1">{{ formatMoney(selectedAsset.current_value) }}</p>
              </div>
              <div v-if="selectedAsset.purchase_date" class="bg-slate-50 rounded-xl p-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Purchase Date</label>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ formatDate(selectedAsset.purchase_date) }}</p>
              </div>
              <div v-if="selectedAsset.warranty_expiry" class="bg-slate-50 rounded-xl p-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Warranty Expiry</label>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ formatDate(selectedAsset.warranty_expiry) }}</p>
              </div>
            </div>

            <!-- Specs -->
            <div v-if="selectedAsset.custom_attributes && Object.keys(selectedAsset.custom_attributes).length" class="border-t border-slate-100 pt-5">
              <h4 class="text-sm font-black text-slate-800 mb-4">Specifications</h4>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="(value, key) in selectedAsset.custom_attributes" :key="key" class="bg-slate-50 rounded-lg p-3">
                  <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ key }}</label>
                  <p class="text-sm font-bold text-slate-700 mt-1">{{ value }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>