<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const locations = ref([]);
const loading = ref(false);
const editingLocation = ref(null);
const form = reactive({
  name: '',
  description: '',
});

const fetchLocations = async () => {
  loading.value = true;
  try {
    // Assuming standard CRUD endpoint exists
    const { data } = await axios.get('/api/locations');
    locations.value = data;
  } catch (error) {
    console.error('Error fetching locations:', error);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  editingLocation.value = null;
  form.name = '';
  form.description = '';
};

const submitLocation = async () => {
  if (!form.name) {
    alert('Location Name is required.');
    return;
  }
  
  const url = editingLocation.value ? `/api/locations/${editingLocation.value.id}` : '/api/locations';
  const method = editingLocation.value ? 'put' : 'post';

  try {
    await axios[method](url, form);
    alert(`Location ${editingLocation.value ? 'updated' : 'created'} successfully!`);
    resetForm();
    fetchLocations();
  } catch (error) {
    console.error(`Error saving location:`, error);
    alert('Failed to save location.');
  }
};

const openEdit = (loc) => {
  editingLocation.value = loc;
  form.name = loc.name;
  form.description = loc.description;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteLocation = async (loc) => {
  if (!confirm(`Are you sure you want to delete the location "${loc.name}"?`)) return;

  try {
    await axios.delete(`/api/locations/${loc.id}`);
    fetchLocations();
  } catch (error) {
    console.error('Error deleting location:', error);
    alert('Failed to delete location.');
  }
};

onMounted(fetchLocations);
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Form -->
    <div class="md:col-span-1">
      <div class="bg-gray-50 p-4 rounded-lg border">
        <h2 class="text-lg font-bold text-gray-700 mb-4">{{ editingLocation ? 'Edit Location' : 'Add Location' }}</h2>
        <form @submit.prevent="submitLocation" class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Name</label>
            <input v-model="form.name" type="text" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" placeholder="e.g. Headquarters" required>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Description</label>
            <textarea v-model="form.description" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-200 outline-none" rows="3" placeholder="Location details..."></textarea>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">{{ editingLocation ? 'Update' : 'Create' }}</button>
            <button v-if="editingLocation" type="button" @click="resetForm" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded hover:bg-gray-300 transition">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- List -->
    <div class="md:col-span-2">
      <div class="overflow-hidden border rounded-lg">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-100 border-b font-bold text-gray-600 uppercase text-xs">
            <tr>
              <th class="p-3">Name</th>
              <th class="p-3">Description</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y bg-white">
            <tr v-if="loading"><td colspan="3" class="p-4 text-center text-gray-500">Loading...</td></tr>
            <tr v-else-if="locations.length === 0"><td colspan="3" class="p-4 text-center text-gray-500">No locations found.</td></tr>
            <tr v-for="loc in locations" :key="loc.id" class="hover:bg-gray-50">
              <td class="p-3 font-medium">{{ loc.name }}</td>
              <td class="p-3 text-gray-500 truncate max-w-xs">{{ loc.description }}</td>
              <td class="p-3 text-right space-x-2">
                <button @click="openEdit(loc)" class="text-blue-600 hover:underline">Edit</button>
                <button @click="deleteLocation(loc)" class="text-red-600 hover:underline">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>