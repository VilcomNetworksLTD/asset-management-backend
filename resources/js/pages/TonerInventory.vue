<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Loader from '@/components/Loader.vue';

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
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Toner Inventory Management</h1>
      <button 
        @click="openModal()" 
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-700 transition shadow-md flex items-center gap-2"
      >
        <i class="fa fa-plus"></i> ADD NEW TONER TYPE
      </button>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <Loader />
    </div>

    <div v-else class="bg-white border rounded-xl overflow-hidden shadow-md">
      <table class="w-full text-left text-sm border-collapse">
        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] border-b">
          <tr>
            <th class="p-4">Toner / Ink Model</th>
            <th class="p-4">Stock Breakdown (Color: Amt)</th>
            <th class="p-4">Total Stock</th>
            <th class="p-4">Price (KES)</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="toner in toners" :key="toner.id" class="hover:bg-gray-50 transition">
            <td class="p-4">
              <div class="font-bold text-gray-700">{{ toner.item_name }}</div>
              <div class="text-[10px] text-gray-400 uppercase font-bold">{{ toner.category }}</div>
            </td>
            <td class="p-4">
              <div class="flex flex-wrap gap-2">
                <span v-for="cs in toner.color_stocks" :key="cs.id" class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded text-xs">
                  <span :style="{ backgroundColor: (cs.color || 'gray').toLowerCase() }" class="w-2 h-2 rounded-full border border-gray-300"></span>
                  <span class="font-bold">{{ cs.color }}:</span>
                  <span :class="cs.in_stock <= cs.min_amt ? 'text-red-600 font-black' : 'text-gray-600'">{{ cs.in_stock }}</span>
                </span>
              </div>
            </td>
            <td class="p-4 font-mono font-bold">{{ toner.color_stocks?.reduce((acc, curr) => acc + curr.in_stock, 0) || 0 }}</td>
            <td class="p-4 text-gray-600 font-medium">{{ toner.price }}</td>
            <td class="p-4">
              <span 
                v-if="toner.status === 'Out of Stock'" 
                class="px-2 py-1 rounded bg-red-100 text-red-700 text-[10px] font-black uppercase"
              >
                Out of Stock
              </span>
              <span 
                v-else-if="toner.status === 'Low Stock'" 
                class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 text-[10px] font-black uppercase"
              >
                Low Stock
              </span>
              <span 
                v-else 
                class="px-2 py-1 rounded bg-green-100 text-green-700 text-[10px] font-black uppercase"
              >
                Healthy
              </span>
            </td>
            <td class="p-4 text-right flex justify-end gap-2 pt-6">
              <button @click="openModal(toner)" class="text-blue-600 hover:text-blue-800 p-1">
                <i class="fa fa-edit text-lg"></i>
              </button>
              <button @click="deleteToner(toner.id)" class="text-red-600 hover:text-red-800 p-1">
                <i class="fa fa-trash text-lg"></i>
              </button>
            </td>
          </tr>
          <tr v-if="toners.length === 0">
            <td colspan="7" class="p-12 text-center text-gray-400 italic">No toner inventory found. Add your first item above.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
          <h3 class="font-bold uppercase text-sm tracking-widest">{{ editingToner ? 'Edit Inventory Item' : 'Register New Toner/Ink' }}</h3>
          <button @click="showModal = false" class="hover:opacity-70"><i class="fa fa-times text-xl"></i></button>
        </div>
        <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Model Name / Description</label>
              <input v-model="form.item_name" type="text" class="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" placeholder="e.g. HP 123A Multi-Pack">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Category</label>
              <select v-model="form.category" class="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm">
                <option value="Toner">Toner</option>
                <option value="Ink">Ink</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Unit Price (KES)</label>
              <input v-model="form.price" type="number" class="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm">
            </div>
          </div>

          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-3">
              <label class="text-[10px] font-bold text-gray-400 uppercase">Manage Color Stocks</label>
              <button @click="addColor" class="text-indigo-600 text-[10px] font-bold hover:underline">
                <i class="fa fa-plus-circle"></i> ADD COLOR
              </button>
            </div>

            <div v-for="(cs, index) in form.color_stocks" :key="index" class="bg-gray-50 p-3 rounded-lg border border-dashed border-gray-200 mb-3 relative group">
              <button 
                v-if="form.color_stocks.length > 1"
                @click="removeColor(index)" 
                class="absolute -top-2 -right-2 bg-red-500 text-white w-5 h-5 rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <i class="fa fa-times"></i>
              </button>
              
              <div class="grid grid-cols-3 gap-2">
                <div>
                  <label class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Color</label>
                  <select v-model="cs.color" class="w-full border p-1 rounded text-xs outline-none">
                    <option value="Black">Black</option>
                    <option value="Cyan">Cyan</option>
                    <option value="Magenta">Magenta</option>
                    <option value="Yellow">Yellow</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Stock</label>
                  <input v-model="cs.in_stock" type="number" class="w-full border p-1 rounded text-xs outline-none">
                </div>
                <div>
                  <label class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Alert Level</label>
                  <input v-model="cs.min_amt" type="number" class="w-full border p-1 rounded text-xs outline-none">
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t">
            <button 
              @click="saveToner" 
              :disabled="submitting || !form.item_name || form.color_stocks.length === 0"
              class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-50 transition shadow-lg"
            >
              {{ submitting ? 'SAVING...' : 'UPDATE INVENTORY' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
