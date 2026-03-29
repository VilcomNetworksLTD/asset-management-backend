<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const categories = ref([]);
const loading = ref(false);
const editingCategory = ref(null);

const form = reactive({
  name: '',
  description: '',
  fields: [] 
});

const fetchCategories = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/categories');
    categories.value = data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  editingCategory.value = null;
  form.name = '';
  form.description = '';
  form.fields = [];
};

const openEdit = (cat) => {
  editingCategory.value = cat;
  form.name = cat.name;
  form.description = cat.description;
  // Ensure fields is an array copy
  form.fields = cat.fields ? JSON.parse(JSON.stringify(cat.fields)) : []; 
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const addField = () => {
  form.fields.push({ key: '', label: '', type: 'text', required: false });
};

const removeField = (index) => {
  form.fields.splice(index, 1);
};

const generateKey = (index) => {
  const field = form.fields[index];
  if (!field.key && field.label) {
    // Simple slugify: "Model Number" -> "model_number"
    field.key = field.label.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_');
  }
};

const submitForm = async () => {
  if (!form.name) return alert('Category Name is required');
  
  try {
    if (editingCategory.value) {
      await axios.put(`/api/categories/${editingCategory.value.id}`, form);
    } else {
      await axios.post('/api/categories', form);
    }
    resetForm();
    fetchCategories();
  } catch (error) {
    console.error(error);
    alert('Failed to save category.');
  }
};

const deleteCategory = async (id) => {
  if (!confirm('Are you sure? This may affect assets linked to this category.')) return;
  try {
    await axios.delete(`/api/categories/${id}`);
    fetchCategories();
  } catch (error) { console.error(error); }
};

onMounted(fetchCategories);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form Side -->
    <div class="lg:col-span-1">
      <div class="bg-white p-5 rounded-lg shadow-sm border sticky top-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">{{ editingCategory ? 'Edit Category' : 'New Category' }}</h2>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Name</label>
            <input v-model="form.name" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-100 outline-none" required />
          </div>
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-100 outline-none"></textarea>
          </div>

          <!-- Field Builder -->
          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-2">
              <label class="text-xs font-bold uppercase text-gray-700">Dynamic Attributes</label>
              <button type="button" @click="addField" class="text-xs text-blue-600 font-bold hover:underline">+ Add Field</button>
            </div>
            <div v-for="(field, i) in form.fields" :key="i" class="bg-gray-50 p-2 rounded mb-2 border relative">
              <button type="button" @click="removeField(i)" class="absolute top-1 right-1 text-gray-400 hover:text-red-500">&times;</button>
              <input v-model="field.label" @input="generateKey(i)" placeholder="Label (e.g. CPU)" class="w-full text-sm border p-1 rounded mb-1" required />
              <div class="grid grid-cols-2 gap-1">
                <input v-model="field.key" placeholder="key" class="text-xs border p-1 rounded bg-gray-100 font-mono" readonly />
                <select v-model="field.type" class="text-xs border p-1 rounded">
                  <option value="text">Text</option>
                  <option value="number">Number</option>
                  <option value="date">Date</option>
                  <option value="textarea">Long Text</option>
                </select>
              </div>
            </div>
          </div>

          <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded font-medium hover:bg-blue-700">{{ editingCategory ? 'Update' : 'Create' }}</button>
            <button v-if="editingCategory" type="button" @click="resetForm" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded hover:bg-gray-200">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- List Side -->
    <div class="lg:col-span-2 space-y-3">
      <div v-for="cat in categories" :key="cat.id" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex justify-between items-start">
        <div>
          <h3 class="font-bold text-gray-800">{{ cat.name }}</h3>
          <p class="text-sm text-gray-500 mb-2">{{ cat.description }}</p>
          <div class="flex flex-wrap gap-1">
            <span v-for="f in cat.fields" :key="f.key" class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-100">{{ f.label }}</span>
          </div>
        </div>
        <div class="space-x-2 text-sm">
          <button @click="openEdit(cat)" class="text-blue-600 font-medium hover:underline">Edit</button>
          <button @click="deleteCategory(cat.id)" class="text-red-600 hover:underline">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>
