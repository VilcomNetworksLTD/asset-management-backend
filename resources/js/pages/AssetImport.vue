<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';
import { AlertCircle, CheckCircle2, FileSpreadsheet, Loader2, UploadCloud } from 'lucide-vue-next';

const router = useRouter();

const file = ref(null);
const categories = ref([]);
const selectedCategoryId = ref('');
const importing = ref(false);
const loadingCategories = ref(true);
const summary = ref(null);
const errorMessage = ref('');

const hasResult = computed(() => !!summary.value);

const handleFileChange = (event) => {
  file.value = event.target.files?.[0] || null;
  summary.value = null;
  errorMessage.value = '';
};

const submitImport = async () => {
  if (!file.value) return;

  importing.value = true;
  summary.value = null;
  errorMessage.value = '';

  const payload = new FormData();
  payload.append('file', file.value);
  payload.append('category_id', selectedCategoryId.value);

  try {
    const { data } = await axios.post('/api/assets/import', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    summary.value = data.summary;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Import failed. Check the file and try again.';
  } finally {
    importing.value = false;
  }
};

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/categories');
    categories.value = data;
  } catch (error) {
    errorMessage.value = 'Failed to load categories. Please refresh and try again.';
  } finally {
    loadingCategories.value = false;
  }
});
</script>

<template>
  <div class="space-y-8">
    <PageHeader title="Inventory" highlight="Import" />

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-8">
      <section class="bg-white border border-gray-100 rounded-[2rem] shadow-sm overflow-hidden">
        <div class="p-8 border-b border-gray-100 flex items-center gap-4">
          <div class="size-12 rounded-2xl bg-blue-50 text-vilcom-blue flex items-center justify-center">
            <FileSpreadsheet class="size-6" />
          </div>
          <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Upload Spreadsheet</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Select the target category before uploading</p>
          </div>
        </div>

        <form @submit.prevent="submitImport" class="p-8 space-y-6">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Target Category</label>
            <select
              v-model="selectedCategoryId"
              class="block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 focus:border-vilcom-blue focus:ring-vilcom-blue"
              required
              :disabled="loadingCategories"
            >
              <option value="" disabled>{{ loadingCategories ? 'Loading categories...' : 'Select category' }}</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <label class="group block border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-vilcom-blue hover:bg-blue-50/40 transition-all">
            <input type="file" accept=".xlsx,.csv" class="hidden" @change="handleFileChange" />
            <UploadCloud class="size-12 text-slate-300 mx-auto mb-4 group-hover:text-vilcom-blue transition-colors" />
            <span class="block text-sm font-black text-slate-700">
              {{ file ? file.name : 'Choose .xlsx or .csv file' }}
            </span>
            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Max 20 MB</span>
          </label>

          <div v-if="errorMessage" class="flex items-start gap-3 rounded-2xl bg-red-50 border border-red-100 p-4 text-red-700">
            <AlertCircle class="size-5 shrink-0 mt-0.5" />
            <p class="text-sm font-bold">{{ errorMessage }}</p>
          </div>

          <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button
              type="button"
              class="px-5 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors"
              @click="router.push({ name: 'assets-list' })"
            >
              Back to Assets
            </button>
            <button
              type="submit"
              :disabled="!file || !selectedCategoryId || importing"
              class="px-6 py-3 rounded-xl bg-vilcom-blue text-white text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-900/20 hover:scale-[1.02] transition-transform disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2"
            >
              <Loader2 v-if="importing" class="size-4 animate-spin" />
              <UploadCloud v-else class="size-4" />
              Import Assets
            </button>
          </div>
        </form>
      </section>

      <aside class="bg-slate-900 text-white rounded-[2rem] shadow-sm overflow-hidden">
        <div class="p-8 border-b border-white/10">
          <h2 class="text-lg font-black tracking-tight">Column Mapping</h2>
        </div>
        <div class="p-8 space-y-5">
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Category</p>
            <p class="text-sm font-bold text-white">Selected before upload</p>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Spreadsheet Core Fields</p>
            <p class="text-sm font-bold text-white/80 leading-6">Asset Name, Serial No, Supplier, Status, Price, Purchase Date, Location</p>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Custom Attributes</p>
            <p class="text-sm font-bold text-white/80 leading-6">Columns must match existing dynamic attribute names or labels for the selected category.</p>
          </div>
        </div>
      </aside>
    </div>

    <section v-if="hasResult" class="bg-white border border-gray-100 rounded-[2rem] shadow-sm overflow-hidden">
      <div class="p-8 border-b border-gray-100 flex items-center gap-3">
        <CheckCircle2 class="size-6 text-green-600" />
        <h2 class="text-xl font-black text-slate-800 tracking-tight">Import Complete</h2>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 p-8">
        <div class="rounded-2xl bg-green-50 p-5">
          <p class="text-3xl font-black text-green-700">{{ summary.imported }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-green-700/60 mt-1">Imported</p>
        </div>
        <div class="rounded-2xl bg-orange-50 p-5">
          <p class="text-3xl font-black text-vilcom-orange">{{ summary.skipped }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-orange-700/60 mt-1">Skipped</p>
        </div>
        <div class="rounded-2xl bg-blue-50 p-5">
          <p class="text-3xl font-black text-vilcom-blue">{{ categories.find((category) => category.id == selectedCategoryId)?.name || '-' }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-blue-700/60 mt-1">Target Category</p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-5">
          <p class="text-3xl font-black text-slate-700">{{ summary.dynamic_attributes_used }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1">Dynamic Fields Used</p>
        </div>
      </div>

      <div v-if="summary.errors?.length" class="px-8 pb-8">
        <div class="rounded-2xl border border-red-100 overflow-hidden">
          <div class="bg-red-50 px-5 py-3 text-[10px] font-black uppercase tracking-widest text-red-700">Rows Needing Attention</div>
          <ul class="divide-y divide-red-50 max-h-64 overflow-y-auto">
            <li v-for="item in summary.errors" :key="item" class="px-5 py-3 text-sm font-medium text-slate-700">{{ item }}</li>
          </ul>
        </div>
      </div>

      <div v-if="summary.ignored_columns?.length" class="px-8 pb-8">
        <div class="rounded-2xl border border-amber-100 overflow-hidden">
          <div class="bg-amber-50 px-5 py-3 text-[10px] font-black uppercase tracking-widest text-amber-700">Ignored Columns</div>
          <div class="p-5 flex flex-wrap gap-2">
            <span
              v-for="column in summary.ignored_columns"
              :key="column"
              class="px-3 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-black"
            >
              {{ column }}
            </span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
