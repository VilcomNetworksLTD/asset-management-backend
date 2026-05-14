<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Html5Qrcode, Html5QrcodeScanner } from "html5-qrcode";
import { QrCode, CheckCircle2, ScanLine, XCircle, Info, ChevronLeft, ArrowRight, RefreshCw, Smartphone } from 'lucide-vue-next';


const props = defineProps({
  targetRoute: {
    type: String,
    default: 'asset-detail'
  }
});

const router = useRouter();
let scannerInstance = null;
const errorMsg = ref('');
const scannedAsset = ref(null);
const loadingDetails = ref(false);

// Audio context for beep sound
const playBeep = () => {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  if (!AudioContext) return;
  
  const ctx = new AudioContext();
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();

  osc.connect(gain);
  gain.connect(ctx.destination);

  osc.type = "sine";
  osc.frequency.setValueAtTime(1200, ctx.currentTime);
  gain.gain.setValueAtTime(0.1, ctx.currentTime);

  osc.start();
  osc.stop(ctx.currentTime + 0.15); // Beep for 150ms
};

const handleScanSuccess = async (barcode) => {
  if (scannerInstance) {
    try {
      await scannerInstance.clear();
    } catch (e) {
      console.warn("Scanner clear failed", e);
    }
  }
  
  playBeep();
  loadingDetails.value = true;
  errorMsg.value = '';

  try {
    // Find asset details by barcode content
    const { data } = await axios.get(`/api/barcodes/${barcode}/details`);
    scannedAsset.value = data;
  } catch (error) {
    console.error(error);
    errorMsg.value = "Asset not found or invalid QR code.";
    
    // Restart scanner after a short delay if failed
    setTimeout(() => {
      initScanner();
      errorMsg.value = '';
    }, 2000);
  } finally {
    loadingDetails.value = false;
  }
};

const onFileChange = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  errorMsg.value = '';
  loadingDetails.value = true;

  const html5QrCode = new Html5Qrcode("reader");
  try {
    const decodedText = await html5QrCode.scanFile(file, true);
    handleScanSuccess(decodedText);
  } catch (err) {
    errorMsg.value = "No valid QR code found in this image.";
    loadingDetails.value = false;
  }
};

const handleScanFailure = (error) => {
  // handle scan failure, usually better to ignore frame errors to avoid spam
  // console.warn(`Code scan error = ${error}`);
};

const resetScanner = async () => {
  scannedAsset.value = null;
  errorMsg.value = '';
  loadingDetails.value = false;
  await nextTick();
  initScanner();
};

const viewAssetDetails = () => {
  if (scannedAsset.value) {
    router.push({ name: props.targetRoute, params: { id: scannedAsset.value.barcode || scannedAsset.value.id } });
  }
};

const initScanner = () => {
  const config = { 
    fps: 10, 
    qrbox: { width: 250, height: 250 },
    aspectRatio: 1.0
  };
  
  scannerInstance = new Html5QrcodeScanner("reader", config, /* verbose= */ false);
  scannerInstance.render(handleScanSuccess, handleScanFailure);
};

onMounted(() => {
  // Slight delay to ensure DOM is ready
  setTimeout(() => {
    initScanner();
  }, 100);
});

onUnmounted(() => {
  if (scannerInstance) {
    scannerInstance.clear().catch(error => {
      console.error("Failed to clear html5-qrcode scanner. ", error);
    });
  }
});
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Rapid <span class="text-vilcom-blue">Scanner</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full animate-ping"></span>
          Asset Tag Recognition Protocol
        </p>
      </div>
      
      <button @click="router.back()" class="p-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-slate-100 transition-all">
        <ChevronLeft class="size-5" />
      </button>
    </div>

    <!-- Scanner View -->
    <div v-if="!scannedAsset" class="bg-white rounded-[3.5rem] shadow-sm border border-gray-100 overflow-hidden relative">
      <div class="p-12 space-y-10">
        <div class="flex items-center gap-5">
          <div class="p-4 bg-vilcom-blue text-white rounded-[1.5rem] shadow-xl shadow-blue-900/20">
            <ScanLine class="size-6" />
          </div>
          <div>
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter">Initialize Lens</h3>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">QR & Barcode Acquisition</p>
          </div>
        </div>

        <div class="relative rounded-[2.5rem] overflow-hidden bg-slate-900 border-[8px] border-slate-50 shadow-inner group">
          <div id="reader" class="w-full aspect-square md:aspect-video object-cover opacity-90 group-hover:opacity-100 transition-opacity"></div>
          
          <!-- Scanner Overlay Guide -->
          <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="size-48 border-2 border-vilcom-orange/50 rounded-3xl relative">
              <div class="absolute -top-1 -left-1 size-6 border-t-4 border-l-4 border-vilcom-orange rounded-tl-xl"></div>
              <div class="absolute -top-1 -right-1 size-6 border-t-4 border-r-4 border-vilcom-orange rounded-tr-xl"></div>
              <div class="absolute -bottom-1 -left-1 size-6 border-b-4 border-l-4 border-vilcom-orange rounded-bl-xl"></div>
              <div class="absolute -bottom-1 -right-1 size-6 border-b-4 border-r-4 border-vilcom-orange rounded-br-xl"></div>
              <div class="absolute inset-0 bg-vilcom-orange/5 animate-pulse rounded-3xl"></div>
            </div>
          </div>
        </div>

        <div v-if="loadingDetails" class="flex items-center justify-center gap-3 py-4">
          <RefreshCw class="size-5 text-vilcom-blue animate-spin" />
          <span class="text-[10px] font-black text-vilcom-blue uppercase tracking-widest">Decoding Stream...</span>
        </div>

        <div v-if="errorMsg" class="flex items-center gap-3 p-6 bg-red-50 border border-red-100 rounded-2xl animate-in slide-in-from-top-2">
          <XCircle class="size-5 text-red-500" />
          <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">{{ errorMsg }}</span>
        </div>

        <div class="flex items-center justify-center gap-8 pt-4">
           <div class="flex flex-col items-center gap-2">
             <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-300">
               <Smartphone class="size-5" />
             </div>
             <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Mobile Optimized</span>
           </div>
           <div class="flex flex-col items-center gap-2">
             <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-300">
               <QrCode class="size-5" />
             </div>
             <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">High Definition</span>
           </div>
        </div>
      </div>
    </div>

    <!-- Result View -->
    <div v-else class="bg-white rounded-[3.5rem] shadow-sm border border-gray-100 overflow-hidden animate-in zoom-in-95 duration-500">
      <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-12 text-center text-white relative">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIgZmlsbD0iI2ZmZiIgZmlsbC1vcGFjaXR5PSIwLjEifS8+PC9zdmc+')] opacity-20"></div>
        <div class="size-24 bg-white/20 backdrop-blur-md rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
          <CheckCircle2 class="size-12 text-white" />
        </div>
        <h2 class="text-3xl font-black tracking-tight mb-2">Identification <span class="opacity-70">Complete</span></h2>
        <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest">Registry Match Found</p>
      </div>
      
      <div class="p-12 space-y-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="col-span-2 space-y-2 text-center md:text-left bg-slate-50 p-8 rounded-3xl border border-gray-100">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">System Identity</label>
            <p class="text-3xl font-black text-slate-800 tracking-tighter">{{ scannedAsset.Asset_Name }}</p>
          </div>
          
          <div class="space-y-2 bg-slate-50 p-6 rounded-3xl border border-gray-100">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Asset Tag</label>
            <p class="text-lg font-black text-vilcom-blue font-mono tracking-tighter">{{ scannedAsset.barcode }}</p>
          </div>

          <div class="space-y-2 bg-slate-50 p-6 rounded-3xl border border-gray-100">
            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Serial Reference</label>
            <p class="text-lg font-black text-slate-700 font-mono tracking-tighter">{{ scannedAsset.Serial_No || 'N/A' }}</p>
          </div>
        </div>
        
        <div class="flex flex-col md:flex-row gap-4 pt-6">
          <button @click="viewAssetDetails" class="flex-1 bg-vilcom-blue text-white py-5 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/30 hover:bg-blue-700 transition-all flex items-center justify-center gap-3">
            Interface Detail
            <ArrowRight class="size-4" />
          </button>
          <button @click="resetScanner" class="px-10 bg-slate-50 text-slate-400 py-5 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-slate-100 transition-all">
            Recapture
          </button>
        </div>
      </div>
    </div>
  </div>
</template>