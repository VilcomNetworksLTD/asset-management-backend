<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Software <span class="text-vilcom-blue">Licenses</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Digital Assets & Entitlements
        </p>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Product Authorization</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Encrypted Key</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Expiration Profile</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Acquisition Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="4" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Validating credentials...
              </td>
            </tr>
            <tr v-for="license in licenses" :key="license.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <ClipboardList class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ license.name }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-2 font-mono text-[10px] text-gray-500 font-bold bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 w-fit">
                  <Key class="size-3 text-vilcom-orange" />
                  {{ license.product_key }}
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="text-[10px] font-black text-slate-600 uppercase tracking-widest">
                  {{ formatDate(license.expiry_date) }}
                </div>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center justify-end gap-2">
                  <Clock class="size-3 text-vilcom-blue" />
                  {{ formatDate(license.pivot.created_at) }}
                </div>
              </td>
            </tr>
            <tr v-if="!loading && licenses.length === 0">
              <td colspan="4" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                No software entitlements detected for your profile.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { ClipboardList, Key, Clock, ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';


const licenses = ref([]);
const loading = ref(true);

const fetchMyLicenses = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/my-licenses');
    licenses.value = data;
  } catch (error) {
    console.error("Failed to fetch user's licenses:", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

onMounted(fetchMyLicenses);
</script>
