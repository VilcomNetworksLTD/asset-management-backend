<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const categories = ref([]);
const loading = ref(false);
const editingCategory = ref(null);
const form = reactive({
  name: '',
  description: '',
  fields: [],
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

const openEdit = (category) => {
  editingCategory.value = category;
  form.name = category.name;
  form.description = category.description;
  form.fields = category.fields ? JSON.parse(JSON.stringify(category.fields)) : [];
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const addField = () => {
  form.fields.push({ name: '', label: '', type: 'text', required: false });
};

const removeField = (index) => {
  form.fields.splice(index, 1);
};

const generateName = (index) => {
  const field = form.fields[index];

  if (!field.label) {
    field.name = '';
    return;
  }

  const base = field.label.trim().toLowerCase().replace(/\s+/g, '_');
  const existingNames = new Set(
    form.fields
      .filter((_, idx) => idx !== index)
      .map(item => item.name)
      .filter(Boolean)
  );

  let unique = base;
  let counter = 1;

  while (existingNames.has(unique)) {
    unique = `${base}_${counter}`;
    counter++;
  }

  field.name = unique;
};

const submitForm = async () => {
  if (!form.name) {
    alert('Category Name is required');
    return;
  }

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

const deleteCategory = async (category) => {
  if (!confirm(`Are you sure you want to delete the category "${category.name}"? This may affect linked assets.`)) return;

  try {
    await axios.delete(`/api/categories/${category.id}`);
    fetchCategories();
  } catch (error) {
    console.error(error);
  }
};

onMounted(fetchCategories);
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="md:col-span-1">
      <div class="bg-gray-50 p-4 rounded-lg border">
        <h2 class="text-lg font-bold text-gray-700 mb-4">{{ editingCategory ? 'Edit Category' : 'Add Category' }}</h2>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Name</label>
            <input v-model="form.name" type="text" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="e.g. Laptop" required>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="Category details..."></textarea>
          </div>
          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-2">
              <label class="text-xs font-bold uppercase text-gray-700">Dynamic Attributes</label>
            </div>
            <button type="button" @click="addField" class="w-full mb-3 border border-blue-200 bg-blue-50 text-blue-700 text-sm font-bold py-2 rounded hover:bg-blue-100 transition">
              + Add Field
            </button>
            <div v-for="(field, i) in form.fields" :key="i" class="bg-gray-50 p-2 rounded mb-2 border relative">
              <button type="button" @click="removeField(i)" class="absolute top-1 right-1 text-gray-400 hover:text-red-500">&times;</button>
              <input v-model="field.label" @input="generateName(i)" placeholder="Label (e.g. CPU)" class="w-full text-sm border p-1 rounded mb-1" required />
              <div class="grid grid-cols-2 gap-1">
                <select v-model="field.type" class="text-xs border p-1 rounded">
                  <option value="text">Text</option>
                  <option value="number">Number</option>
                  <option value="date">Date</option>
                  <option value="textarea">Long Text</option>
                  <option value="email">Email</option>
                  <option value="mac_address">MAC Address</option>
                  <option value="ip_address">IP Address</option>
                  <option value="select">Dropdown</option>
                  <option value="checkbox">Checkbox</option>
                  <option value="image">Image</option>
                  <option value="file">File</option>
                </select>
              </div>
              <div v-if="field.type === 'select'" class="mt-1">
                <input v-model="field.options" placeholder="Options (comma separated)" class="w-full text-xs border p-1 rounded" />
              </div>
              <div class="mt-1 flex items-center gap-2">
                <input v-model="field.required" type="checkbox" :id="`category_req_${i}`" class="rounded" />
                <label :for="`category_req_${i}`" class="text-xs text-gray-600">Required</label>
              </div>
            </div>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">{{ editingCategory ? 'Update' : 'Create' }}</button>
            <button v-if="editingCategory" type="button" @click="resetForm" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded hover:bg-gray-300 transition">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div class="md:col-span-2">
      <div class="overflow-hidden border rounded-lg">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-100 border-b font-bold text-gray-600 uppercase text-xs">
            <tr>
              <th class="p-3">Name</th>
              <th class="p-3">Description</th>
              <th class="p-3">Attributes</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y bg-white">
            <tr v-if="loading"><td colspan="4" class="p-4 text-center text-gray-500">Loading...</td></tr>
            <tr v-else-if="categories.length === 0"><td colspan="4" class="p-4 text-center text-gray-500">No categories found.</td></tr>
            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
              <td class="p-3 font-medium">{{ category.name }}</td>
              <td class="p-3 text-gray-500 truncate max-w-xs">{{ category.description }}</td>
              <td class="p-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="field in category.fields" :key="field.name" class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-100">{{ field.label }}</span>
                </div>
              </td>
              <td class="p-3 text-right space-x-2">
                <button @click="openEdit(category)" class="text-blue-600 hover:underline">Edit</button>
                <button @click="deleteCategory(category)" class="text-red-600 hover:underline">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
