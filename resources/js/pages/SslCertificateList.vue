<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Security <span class="text-vilcom-blue">Certificates</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Monitoring SSL/TLS Lifecycle & Encryption Status
        </p>
      </div>
      
      <button @click="showManualModal = true" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        Register Certificate
      </button>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <!-- Search & Filters -->
      <div class="p-8 border-b border-gray-50 flex flex-wrap gap-4 items-center bg-gray-50/30 border-l-4 border-vilcom-blue">
        <div class="relative group flex-1">
          <input 
            v-model="filters.search" 
            @input="debouncedSearch" 
            class="bg-white border-none rounded-xl py-3 pl-10 pr-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all w-full shadow-sm" 
            placeholder="Search domain, issuer, or IP protocol..." 
          />
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-300 group-focus-within:text-vilcom-blue transition-colors" />
        </div>

        <select v-model="filters.status" @change="fetchData" class="bg-white border-none rounded-xl py-3 px-6 text-xs font-bold ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none min-w-[200px] shadow-sm text-slate-600">
          <option value="">All Security Statuses</option>
          <option value="valid">Valid / Secure</option>
          <option value="expiring_soon">Expiring Soon</option>
          <option value="expired">Security Breach (Expired)</option>
        </select>
      </div>

      <!-- Table View -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Domain Topology</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">CA Authority</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Timeline</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Protocol Integrity</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="5" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Analyzing encryption headers...
              </td>
            </tr>
            <tr v-for="cert in certificates" :key="cert.id" class="group hover:bg-slate-50 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Globe class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ cert.common_name }}
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 font-mono">
                      {{ cert.domain || 'INTERNAL-IP' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="space-y-1">
                  <div class="text-xs font-black text-slate-700">{{ cert.ca_vendor || 'Self-Signed Identity' }}</div>
                  <div class="text-[9px] font-bold text-gray-400 font-mono tracking-widest">{{ cert.serial_number?.substring(0, 16).toUpperCase() }}...</div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="space-y-1">
                  <div class="text-xs font-black text-slate-600">{{ formatDate(cert.expiry_date) }}</div>
                  <div class="text-[9px] font-black uppercase tracking-widest" :class="getDaysClass(cert.days_to_expiry)">
                    {{ cert.days_to_expiry }} Days to breach
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 text-center">
                <div class="flex flex-col items-center gap-1">
                  <span :class="getStatusClass(cert.status)" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest ring-1 ring-white/50">
                    {{ formatStatus(cert.status) }}
                  </span>
                  <div v-if="cert.revocation_status === 'valid'" class="text-teal-600 text-[8px] font-black uppercase tracking-widest flex items-center gap-1">
                    <CheckCircle2 class="size-3" /> Integrity Verified
                  </div>
                </div>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="scanCert(cert.id)" class="p-2.5 bg-white border border-gray-100 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white hover:border-vilcom-blue transition-all shadow-sm" title="Live Scan">
                    <RefreshCcw class="size-4" />
                  </button>
                  <button @click="deleteCert(cert.id)" class="p-2.5 bg-white border border-gray-100 text-red-500 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all shadow-sm" title="Purge Record">
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="p-8 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
          Node {{ pagination.current_page }} of {{ pagination.last_page }} <span class="mx-2 text-gray-200">|</span> Total Assets: {{ pagination.total }}
        </div>
        <div class="flex items-center gap-3">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronLeft class="size-4" />
          </button>
          <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="p-3 border border-gray-100 rounded-xl bg-white hover:bg-gray-50 disabled:opacity-20 transition-all font-black text-xs">
            <ChevronRight class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Scan Modal -->
    <div v-if="showScanModal" class="fixed inset-0 z-[2000] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeScanModal"></div>
      <div class="relative bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-10 space-y-8">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-vilcom-blue text-white rounded-2xl shadow-lg shadow-blue-900/20">
              <ShieldCheck class="size-6" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-800 tracking-tight">Security Analysis</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Live Certificate Inspection</p>
            </div>
          </div>

          <div v-if="scannedData" class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100 space-y-4">
            <div>
              <label class="text-[9px] font-black text-blue-400 uppercase tracking-[0.2em]">Common Name</label>
              <div class="text-sm font-black text-slate-800 break-all">{{ scannedData.common_name }}</div>
            </div>
            <div>
              <label class="text-[9px] font-black text-blue-400 uppercase tracking-[0.2em]">Expiration Profile</label>
              <div class="text-sm font-black text-slate-800">{{ scannedData.expiry_date }}</div>
            </div>
          </div>

          <button @click="closeScanModal" class="w-full py-4 bg-slate-800 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all">
            Terminate Session
          </button>
        </div>
      </div>
    </div>

    <SslCertificateModal :show="showManualModal" :users="users" @close="showManualModal = false" @saved="fetchData" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
// NEW: Import the manual modal component
import SslCertificateModal from '../components/SslCertificateModal.vue';
import { ShieldCheck, Plus, Search, ChevronLeft, ChevronRight, Globe, Server, Activity, Trash2, RefreshCcw, ShieldAlert, CheckCircle2, X } from 'lucide-vue-next';


const loading = ref(false);
const certificates = ref([]);
const users = ref([]); // Added to store users for the modal dropdown
const pagination = ref({});
const filters = reactive({ search: '', status: '', page: 1 });

const showScanModal = ref(false);
const showManualModal = ref(false); // NEW: State for the manual modal
const scanForm = reactive({ domain: '', port: 443, installed_on_type: 'web_server', installed_on: '' });
const isScanning = ref(false);
const scannedData = ref(null);
const scanError = ref(null);

let debounceTimeout = null;

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/ssl-certificates', { params: filters });
        certificates.value = response.data.data;
        pagination.value = response.data;
        if (response.data.meta) pagination.value = response.data.meta;
    } catch (error) {
        console.error("Error fetching certificates:", error);
    } finally {
        loading.value = false;
    }
};

// Optional: Fetch users if you want the "Owner" dropdown to work
const fetchUsers = async () => {
    try {
        const { data } = await axios.get('/api/users');
        users.value = data;
    } catch (e) {
        console.error("Failed to fetch users");
    }
};

const debouncedSearch = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => { filters.page = 1; fetchData(); }, 400);
};

const changePage = (page) => {
    filters.page = page;
    fetchData();
};

const scanCert = async (id) => {
    if(!confirm("Perform a live scan for this certificate?")) return;
    try {
        await axios.post(`/api/ssl-certificates/${id}/scan`);
        fetchData();
    } catch (e) {
        alert("Scan failed: " + (e.response?.data?.message || e.message));
    }
};

const deleteCert = async (id) => {
    if(!confirm("Are you sure you want to delete this certificate?")) return;
    try { await axios.delete(`/api/ssl-certificates/${id}`); fetchData(); } catch (e) { alert("Delete failed."); }
};

const openScanModal = () => {
    scanForm.domain = '';
    scanForm.port = 443;
    scannedData.value = null;
    scanError.value = null;
    showScanModal.value = true;
};

const closeScanModal = () => {
    showScanModal.value = false;
};

const performScan = async () => {
    if (!scanForm.domain) return;
    isScanning.value = true;
    scanError.value = null;
    scannedData.value = null;
    try {
        const res = await axios.post('/api/ssl-certificates/scan-domain', {
            host: scanForm.domain,
            port: scanForm.port
        });
        if (!res.data || Object.keys(res.data).length === 0) {
            scanError.value = "Could not retrieve certificate. Check domain/port.";
        } else {
            scannedData.value = res.data;
        }
    } catch (e) {
        scanError.value = e.response?.data?.message || "Scan failed.";
    } finally {
        isScanning.value = false;
    }
};

const saveScannedCert = async () => {
    if (!scannedData.value) return;
    try {
        await axios.post('/api/ssl-certificates', {
            ...scannedData.value,
            port: scanForm.port,
            installed_on_type: scanForm.installed_on_type,
            installed_on: 'Manual Scan (' + scanForm.domain + ')',
        });
        closeScanModal();
        fetchData();
    } catch (e) {
        alert("Failed to save: " + (e.response?.data?.message || e.message));
    }
};

const alertNotImplemented = () => alert("This feature requires a modal component implementation.");

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '-';
const formatStatus = (s) => s ? s.replace('_', ' ').toUpperCase() : 'UNKNOWN';
const getDaysClass = (d) => d <= 7 ? 'text-red-600' : (d <= 30 ? 'text-yellow-600' : 'text-green-600');
const getStatusClass = (s) => ({ 'valid': 'bg-green-100 text-green-800', 'expiring_soon': 'bg-yellow-100 text-yellow-800', 'expired': 'bg-red-100 text-red-800' }[s] || 'bg-gray-100 text-gray-800');
const getRowClass = (s) => {
    return { 
        'valid': 'bg-green-50 hover:bg-green-100 border-l-4 border-green-500', 
        'expiring_soon': 'bg-yellow-50 hover:bg-yellow-100 border-l-4 border-yellow-500', 
        'expired': 'bg-red-50 hover:bg-red-100 border-l-4 border-red-500' 
    }[s] || 'bg-white hover:bg-gray-50';
};

onMounted(() => {
    fetchData();
    fetchUsers(); // Load users for the modal
});
</script>