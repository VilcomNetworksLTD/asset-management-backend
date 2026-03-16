<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3c8dbc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        SSL Certificates
      </h1>
      <div class="flex gap-2">
        <button @click="showManualModal = true" class="bg-[#3c8dbc] hover:bg-[#367fa9] text-white px-4 py-2 rounded shadow-sm text-sm font-medium transition flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Register Certificate
        </button>
      </div>
    </div>

    <div class="bg-white p-4 rounded shadow-sm mb-6 flex flex-col md:flex-row gap-4 items-center border-l-4 border-[#3c8dbc]">
      <div class="flex-1 w-full relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </span>
        <input 
          v-model="filters.search" 
          @input="debouncedSearch"
          type="search" 
          class="w-full py-2 pl-10 pr-4 text-sm bg-gray-50 border border-gray-200 rounded-md focus:ring-2 focus:ring-[#3c8dbc] focus:bg-white focus:outline-none transition-all" 
          placeholder="Search domain, issuer, or IP..." 
        >
      </div>
      <div class="w-full md:w-48">
        <select v-model="filters.status" @change="fetchData" class="block w-full py-2 px-3 border border-gray-200 bg-gray-50 rounded-md text-sm focus:ring-2 focus:ring-[#3c8dbc] outline-none">
          <option value="">All Statuses</option>
          <option value="valid">Valid</option>
          <option value="expiring_soon">Expiring Soon</option>
          <option value="expired">Expired</option>
        </select>
      </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Domain Information</th>
            <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Issuer & Serial</th>
            <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Expiry Timeline</th>
            <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider text-center">Security Check</th>
            <th class="px-6 py-3 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white text-[13px]">
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic font-light">Analyzing certificates...</td>
          </tr>
          <tr v-else-if="certificates.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-400">No active certificates found.</td>
          </tr>
          <tr v-for="cert in certificates" :key="cert.id" :class="[getRowClass(cert.status), 'hover:bg-gray-50 transition-colors']">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="p-2 rounded bg-blue-50">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3c8dbc" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
                <div>
                  <div class="font-bold text-gray-900 uppercase tracking-tight">{{ cert.common_name }}</div>
                  <div class="text-[11px] text-gray-500 font-mono italic">{{ cert.ip_address || 'Unmapped' }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-gray-900 font-medium">{{ cert.ca_vendor || 'Self-Signed' }}</div>
              <div class="text-[10px] text-gray-400 font-mono tracking-widest">{{ cert.serial_number ? cert.serial_number.substring(0, 12).toUpperCase() : 'N/A' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-gray-900">{{ formatDate(cert.expiry_date) }}</div>
              <div class="text-[11px] font-bold" :class="getDaysClass(cert.days_to_expiry)">
                {{ cert.days_to_expiry }} days remaining
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <div class="flex flex-col items-center gap-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(cert.status)">
                  {{ formatStatus(cert.status) }}
                </span>
                <span v-if="cert.revocation_status === 'valid'" class="text-green-600 text-[10px] flex items-center gap-1 font-bold uppercase">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> Valid
                </span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center justify-center gap-4">
                <button @click="scanCert(cert.id)" class="text-[#3c8dbc] hover:text-blue-800 transition-colors" title="Sync/Scan">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                </button>
                <button @click="deleteCert(cert.id)" class="text-red-500 hover:text-red-700 transition-colors" title="Delete Certificate">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex justify-between items-center text-xs text-gray-500 uppercase font-bold" v-if="pagination.total > 0">
      <div>Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }}</div>
      <div class="flex gap-1">
        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1.5 border border-gray-200 rounded bg-white hover:bg-gray-50 disabled:opacity-40 transition-all">Prev</button>
        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1.5 border border-gray-200 rounded bg-white hover:bg-gray-50 disabled:opacity-40 transition-all">Next</button>
      </div>
    </div>

    <div v-if="showScanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-60 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-2xl w-full max-w-md overflow-hidden border-t-8 border-[#3c8dbc]">
        <div class="p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 text-uppercase tracking-tight">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            Certificate Analysis
          </h3>
          <div v-if="scannedData" class="bg-blue-50 p-4 rounded-md border border-blue-100 text-[13px] space-y-1 font-mono">
            <p><span class="text-gray-500 font-bold uppercase text-[10px]">Common Name:</span> <br><span class="text-blue-900 font-bold">{{ scannedData.common_name }}</span></p>
            <p><span class="text-gray-500 font-bold uppercase text-[10px] mt-2 block">Expiry:</span> <br><span class="text-blue-800 font-bold">{{ scannedData.expiry_date }}</span></p>
          </div>
        </div>
        <div class="bg-gray-50 p-4 flex flex-row-reverse gap-2">
          <button @click="closeScanModal" class="bg-white border border-gray-300 text-gray-600 px-5 py-2 rounded text-xs font-bold hover:bg-gray-100 transition-all uppercase">Close</button>
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