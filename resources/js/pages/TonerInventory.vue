<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Loader from '@/components/Loader.vue';
import { Printer, Plus, Search, Layers, Activity, AlertCircle, Edit3, Trash2, ChevronLeft, ChevronRight, X, Droplets, Package } from 'lucide-vue-next';


const toners = ref([]);
const loading = ref(false);
const submitting = ref(false);
const showModal = ref(false);
const editingToner = ref(null);

const form = ref({
  item_name: '',
  category: 'Toner',
  price: 0,
  color_stocks: [] // Array of { color: '', in_stock: 0, min_amt: 5 }
});

const addColor = () => {
  form.value.color_stocks.push({ color: '', in_stock: 0, min_amt: 5 });
};

const removeColor = (index) => {
  form.value.color_stocks.splice(index, 1);
};

const fetchToners = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/consumables/list');
    toners.value = (data.data || data).filter(c => 
      ['Toner', 'Ink'].includes(c.category)
    );
  } catch (error) {
    console.error('Failed to fetch toners', error);
  } finally {
    loading.value = false;
  }
};

const openModal = (toner = null) => {
  if (toner) {
    editingToner.value = toner;
    form.value = { 
      item_name: toner.item_name,
      category: toner.category,
      price: toner.price,
      color_stocks: toner.color_stocks ? toner.color_stocks.map(cs => ({ ...cs })) : []
    };
  } else {
    editingToner.value = null;
    form.value = {
      item_name: '',
      category: 'Toner',
      price: 0,
      color_stocks: [{ color: 'Black', in_stock: 0, min_amt: 5 }]
    };
  }
  showModal.value = true;
};

const saveToner = async () => {
  if (form.value.color_stocks.length === 0) {
    return alert('Please add at least one color and stock level.');
  }
  
  submitting.value = true;
  try {
    if (editingToner.value) {
      await axios.put(`/api/consumables/${editingToner.value.id}`, form.value);
    } else {
      await axios.post('/api/consumables', form.value);
    }
    showModal.value = false;
    fetchToners();
  } catch (err) {
    alert(err.response?.data?.message || 'Error saving toner');
  } finally {
    submitting.value = false;
  }
};

const deleteToner = async (id) => {
  if (!confirm('Are you sure you want to remove this toner type from inventory?')) return;
  try {
    await axios.delete(`/api/consumables/${id}`);
    fetchToners();
  } catch (err) {
    alert('Error deleting toner');
  }
};

onMounted(fetchToners);
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Supply <span class="text-vilcom-blue">Inventory</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Managed Printing & Consumables
        </p>
      </div>
      
      <button @click="openModal()" class="bg-vilcom-blue text-white px-8 py-4 rounded-2xl shadow-xl shadow-blue-900/10 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all">
        <Plus class="size-4" />
        New Consumable Type
      </button>
    </div>

    <!-- Table View -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden relative">
      <div v-if="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center p-20">
        <Loader />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Consumable Identity</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Chromatic Allocation</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Aggregate Stock</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Valuation</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Operations</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="toner in toners" :key="toner.id" class="group hover:bg-blue-50/30 transition-all duration-300">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="size-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
                    <Droplets class="size-5" />
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">
                      {{ toner.item_name }}
                    </div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">{{ toner.category }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex flex-wrap gap-2 max-w-[240px]">
                  <div v-for="cs in toner.color_stocks" :key="cs.id" 
                       class="flex items-center gap-2 bg-gray-50 border border-gray-100 px-3 py-1.5 rounded-xl transition-all group/stock">
                    <div :style="{ backgroundColor: (cs.color || 'gray').toLowerCase() }" 
                         class="size-2 rounded-full border border-gray-200 shadow-sm"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-tight">{{ cs.color }}</span>
                    <span :class="cs.in_stock <= cs.min_amt ? 'text-red-600 animate-pulse' : 'text-vilcom-blue'" class="text-[10px] font-black">
                      {{ cs.in_stock }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="flex items-center gap-2">
                  <Layers class="size-4 text-gray-300" />
                  <span class="text-sm font-black text-slate-800">
                    {{ toner.color_stocks?.reduce((acc, curr) => acc + curr.in_stock, 0) || 0 }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-5">
                <div class="text-[11px] font-black text-slate-600">
                  <span class="text-[9px] text-gray-400 mr-1">KSh</span>
                  {{ toner.price }}
                </div>
              </td>
              <td class="px-6 py-5 text-center">
                <span v-if="toner.status === 'Out of Stock'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-red-50 text-red-600 ring-1 ring-red-100">
                  Depleted
                </span>
                <span v-else-if="toner.status === 'Low Stock'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-orange-50 text-vilcom-orange ring-1 ring-orange-100 animate-pulse">
                  Reorder
                </span>
                <span v-else class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-green-50 text-green-700 ring-1 ring-green-100">
                  Optimal
                </span>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openModal(toner)" class="p-2.5 bg-white border border-gray-100 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white hover:border-vilcom-blue transition-all shadow-sm">
                    <Edit3 class="size-4" />
                  </button>
                  <button @click="deleteToner(toner.id)" class="p-2.5 bg-white border border-gray-100 text-red-500 rounded-xl hover:bg-red-600 hover:text-white hover:border-red-600 transition-all shadow-sm">
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && toners.length === 0">
              <td colspan="6" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                Inventory baseline not established.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-[3000] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white w-full max-w-xl rounded-[3.5rem] shadow-2xl p-12 overflow-hidden border border-slate-100 animate-in zoom-in-95 duration-300">
          <div class="absolute top-0 right-0 p-10">
            <button @click="showModal = false" class="text-slate-300 hover:text-red-500 transition-colors">
              <X class="size-6" />
            </button>
          </div>

          <div class="flex items-center gap-5 mb-10">
            <div class="p-4 bg-vilcom-blue text-white rounded-[1.5rem] shadow-xl shadow-blue-900/20">
              <Package class="size-6" />
            </div>
            <div>
              <h3 class="text-2xl font-black text-slate-800 tracking-tighter">{{ editingToner ? 'Update Registry' : 'New System Entry' }}</h3>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Inventory Configuration Profile</p>
            </div>
          </div>

          <div class="space-y-8 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="col-span-2 space-y-3">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Consumable Name</label>
                <input v-model="form.item_name" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all" placeholder="e.g. HP 123A Multi-Pack">
              </div>
              <div class="space-y-3">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Category</label>
                <select v-model="form.category" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none">
                  <option value="Toner">Toner Unit</option>
                  <option value="Ink">Ink Reservoir</option>
                </select>
              </div>
              <div class="space-y-3">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Unit Valuation (KSh)</label>
                <input v-model="form.price" type="number" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue transition-all">
              </div>
            </div>

            <div class="space-y-6 pt-4">
              <div class="flex items-center justify-between">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Chromatic Stock Management</label>
                <button @click="addColor" class="flex items-center gap-2 text-vilcom-blue text-[10px] font-black uppercase tracking-widest hover:opacity-70 transition-all">
                  <Plus class="size-3" />
                  Append Channel
                </button>
              </div>

              <div class="space-y-4">
                <div v-for="(cs, index) in form.color_stocks" :key="index" class="relative bg-slate-50 p-6 rounded-3xl border border-gray-100 group/item">
                  <button v-if="form.color_stocks.length > 1" @click="removeColor(index)" class="absolute -top-2 -right-2 size-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-all shadow-lg">
                    <X class="size-3" />
                  </button>
                  
                  <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-2">
                      <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Color</label>
                      <select v-model="cs.color" class="w-full bg-white border-none rounded-xl p-3 text-[11px] font-black shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue appearance-none">
                        <option value="Black">Black</option>
                        <option value="Cyan">Cyan</option>
                        <option value="Magenta">Magenta</option>
                        <option value="Yellow">Yellow</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div class="space-y-2">
                      <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Stock</label>
                      <input v-model="cs.in_stock" type="number" class="w-full bg-white border-none rounded-xl p-3 text-[11px] font-black shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue">
                    </div>
                    <div class="space-y-2">
                      <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Alert @</label>
                      <input v-model="cs.min_amt" type="number" class="w-full bg-white border-none rounded-xl p-3 text-[11px] font-black shadow-sm ring-1 ring-gray-100 focus:ring-2 focus:ring-vilcom-blue">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-12">
            <button @click="saveToner" :disabled="submitting || !form.item_name || form.color_stocks.length === 0" 
                    class="w-full bg-vilcom-blue text-white py-5 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/30 hover:bg-blue-700 disabled:opacity-30 transition-all active:scale-95">
              {{ submitting ? 'Transmitting Data...' : 'Commit to Inventory' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

