<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Html5Qrcode, Html5QrcodeScanner } from "html5-qrcode";

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
  <div class="p-6 max-w-2xl mx-auto">
    
    <!-- Scanner View -->
    <div v-if="!scannedAsset" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="mb-4 text-center">
        <h1 class="text-2xl font-bold text-gray-800">Quick Scan</h1>
        <p class="text-gray-500">Point your camera at the asset tag</p>
      </div>

      <div id="reader" class="overflow-hidden rounded-lg bg-black"></div>
      <div v-if="loadingDetails" class="text-center mt-4 text-blue-600 font-bold animate-pulse">Fetching Asset Details...</div>

      <div v-if="errorMsg" class="mt-4 p-3 bg-red-100 text-red-700 rounded text-center font-medium">{{ errorMsg }}</div>
    </div>

    <!-- Result View (No Redirect) -->
    <div v-else class="bg-white rounded-xl shadow-lg border border-indigo-100 overflow-hidden animate-in fade-in slide-in-from-bottom-4">
      <div class="bg-green-600 p-4 text-center text-white">
        <i class="fa fa-check-circle text-4xl mb-2 block"></i>
        <h2 class="text-xl font-bold">Scan Successful</h2>
      </div>
      
      <div class="p-6 text-center space-y-4">
        <div>
          <p class="text-xs font-bold text-gray-400 uppercase">Asset Name</p>
          <p class="text-2xl font-bold text-gray-800">{{ scannedAsset.Asset_Name }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Asset Tag</p>
            <p class="font-mono text-gray-700 font-medium">{{ scannedAsset.barcode }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Serial</p>
            <p class="font-mono text-gray-700 font-medium">{{ scannedAsset.Serial_No || 'N/A' }}</p>
          </div>
        </div>
        
        <div class="pt-6 flex flex-col gap-3">
          <button @click="viewAssetDetails" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
            View Full Details
          </button>
          <button @click="resetScanner" class="w-full bg-gray-100 text-gray-700 py-3 rounded-lg font-bold hover:bg-gray-200 transition">
            Scan Another
          </button>
        </div>
      </div>
    </div>

  </div>
</template>