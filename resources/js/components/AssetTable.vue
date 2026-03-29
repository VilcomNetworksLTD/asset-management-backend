<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const props = defineProps({
  assets: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
});

const router = useRouter();
const route = useRoute();

// Fix 1: Determine the correct detail route based on the current URL path
const goToDetail = (id) => {
  // If the current path contains '/user', we use the HOD/User route name
  const isUserDash = route.path.includes('/dashboard/user') || route.path.includes('/hod');
  const routeName = isUserDash ? 'user-asset-detail' : 'asset-detail';
  
  router.push({ name: routeName, params: { id } });
};

// Fix 2: Updated print function to use the actual Barcode Image from the API
const printBarcode = async (asset) => {
  if (!asset || !asset.id) return;
  
  const baseUrl = window.location.origin;
  const barcodeImageUrl = `${baseUrl}/api/barcodes/${asset.barcode || asset.id}/image`;
  
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
  
  if (!printWindow) {
    alert("Please allow pop-ups to print barcodes.");
    return;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Barcode - ${asset.Asset_Name}</title>
        <style>
          body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            flex-direction: column; 
            font-family: sans-serif; 
            margin: 0;
          }
          .barcode-container { 
            text-align: center; 
            border: 1px solid #333; 
            padding: 20px; 
            width: 280px;
          }
          img { width: 100%; height: auto; margin-bottom: 10px; }
          .barcode-text { font-family: monospace; font-size: 1.2em; font-weight: bold; }
          .asset-name { margin-top: 5px; font-size: 1em; color: #555; }
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
</script>

<template>
  <div class="bg-white shadow-sm border border-gray-100 rounded-[2rem] overflow-hidden group transition-all duration-300 hover:shadow-lg">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-sm text-left">
        <thead>
          <tr class="bg-slate-50/50">
            <th class="px-8 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Asset Tag</th>
            <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Model Name</th>
            <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Category</th>
            <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Location</th>
            <th class="px-6 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest">Status</th>
            <th class="px-8 py-5 font-black text-[10px] text-gray-400 uppercase tracking-widest text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading">
            <td colspan="6" class="p-12 text-center">
               <div class="flex flex-col items-center gap-3 opacity-40">
                 <i class="fa fa-spinner fa-spin text-2xl"></i>
                 <span class="font-bold text-xs uppercase tracking-widest">Loading specialized inventory...</span>
               </div>
            </td>
          </tr>
          <tr v-else-if="assets.length === 0">
            <td colspan="6" class="p-12 text-center">
               <div class="flex flex-col items-center gap-2 opacity-20">
                 <i class="fa fa-box-open text-3xl"></i>
                 <span class="font-bold">No assets found in target sector.</span>
               </div>
            </td>
          </tr>
          <tr v-for="asset in (assets || []).filter(a => a !== null)" :key="asset.id" class="hover:bg-blue-50/30 transition-colors group/row">
            <td class="px-8 py-5">
              <span class="font-mono text-xs font-black text-vilcom-blue bg-blue-50 px-3 py-1 rounded-lg border border-blue-100/50">
                {{ asset.barcode }}
              </span>
            </td>
            <td class="px-6 py-5">
              <div class="flex flex-col">
                <span class="font-black text-slate-800 text-sm tracking-tight">{{ asset.Asset_Name }}</span>
              </div>
            </td>
            <td class="px-6 py-5">
              <span class="text-xs font-bold text-slate-600">{{ asset.category?.name || asset.Asset_Category || '-' }}</span>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-2">
                <div class="size-2 rounded-full bg-slate-200"></div>
                <span class="text-xs font-medium text-slate-500">{{ asset.location_model?.name || '-' }}</span>
              </div>
            </td>
            <td class="px-6 py-5">
              <span :class="[
                'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm',
                asset.status?.Status_Name?.toLowerCase() === 'deployed' ? 'bg-vilcom-blue text-white' : 'bg-green-100 text-green-700'
              ]">
                {{ asset.status?.Status_Name || 'Ready' }}
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <div class="flex justify-end gap-3 opacity-100 transition-opacity">
                <button 
                  @click="goToDetail(asset.id)" 
                  class="bg-vilcom-blue text-white px-4 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:shadow-lg transition-all active:scale-95 flex items-center gap-2"
                  title="View Details"
                >
                  <i class="fa fa-eye"></i> Details
                </button>
                <button 
                  @click="printBarcode(asset)" 
                  class="p-2 border border-blue-100 text-slate-400 hover:text-vilcom-orange hover:border-vilcom-orange hover:bg-orange-50 rounded-xl transition-all"
                  title="Print Label"
                >
                  <i class="fa fa-print"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
