<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Add SSL Certificate
              </h3>
              <div class="mt-2">
                <form @submit.prevent="submit">
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="common_name">
                      Common Name (Domain)
                    </label>
                    <div class="flex gap-2">
                        <input v-model="form.common_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="common_name" type="text" placeholder="example.com" required>
                        <button type="button" @click="scanDomain" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline whitespace-nowrap" :disabled="scanning || !form.common_name">
                            <span v-if="scanning">Scanning...</span>
                            <span v-else>Scan</span>
                        </button>
                    </div>
                    <p v-if="errors.common_name" class="text-red-500 text-xs italic mt-1">{{ errors.common_name[0] }}</p>
                  </div>

                  <div v-if="form.serial_number" class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded text-xs text-blue-800">
                    <strong>Cert Found:</strong> {{ form.ca_vendor }} (Serial: {{ form.serial_number.substring(0,8) }}...)
                  </div>

                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="port">
                      Port
                    </label>
                    <input v-model="form.port" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="port" type="number">
                  </div>

                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="expiry_date">
                      Expiry Date
                    </label>
                    <input v-model="form.expiry_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="expiry_date" type="date" required>
                    <p v-if="errors.expiry_date" class="text-red-500 text-xs italic mt-1">{{ errors.expiry_date[0] }}</p>
                  </div>

                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="ca_vendor">
                      CA Vendor
                    </label>
                    <input v-model="form.ca_vendor" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="ca_vendor" type="text">
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="installed_on_type">
                        Type
                        </label>
                        <select v-model="form.installed_on_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="installed_on_type">
                          <option value="web_server">Web Server</option>
                          <option value="load_balancer">Load Balancer</option>
                          <option value="application">Application</option>
                          <option value="cdn">CDN</option>
                          <option value="other">Other</option>
                        </select>
                        <p v-if="errors.installed_on_type" class="text-red-500 text-xs italic mt-1">{{ errors.installed_on_type[0] }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="installed_on">
                        Host/Server Name
                        </label>
                        <input v-model="form.installed_on" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="installed_on" type="text" placeholder="e.g. nginx-01">
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="assigned_owner_id">
                      Assigned Owner
                    </label>
                    <select v-model="form.assigned_owner_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" id="assigned_owner_id">
                      <option :value="null">-- Unassigned --</option>
                      <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.department?.name || user.role || 'Staff' }})</option>
                    </select>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button @click="submit" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
            Save Certificate
          </button>
          <button @click="close" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: Boolean,
  users: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'saved']);

// Function to return a fresh initial state
const getInitialState = () => ({
  common_name: '',
  port: 443,
  expiry_date: '',
  ca_vendor: '',
  serial_number: '',
  installed_on: '',
  installed_on_type: 'web_server', 
  assigned_owner_id: null
});

const form = reactive(getInitialState());
const errors = ref({});
const scanning = ref(false);

const close = () => { 
  emit('close'); 
  resetForm(); 
};

const resetForm = () => { 
  Object.assign(form, getInitialState()); 
  errors.value = {}; 
};

const submit = async () => {
  try {
    // Clear old errors
    errors.value = {};
    
    
    console.log("Payload being sent to server:", { ...form });

    await axios.post('/api/ssl-certificates', form);
    emit('saved');
    close();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    console.error("Submission error:", error.response?.data);
  }
};

const scanDomain = async () => {
  if (!form.common_name) return;
  scanning.value = true;
  try {
    const { data } = await axios.post('/api/ssl-certificates/scan-domain', { 
      host: form.common_name, 
      port: form.port 
    });
    
    if (data.expiry_date) form.expiry_date = data.expiry_date;
    if (data.ca_vendor) form.ca_vendor = data.ca_vendor;
    if (data.serial_number) form.serial_number = data.serial_number;
    if (data.common_name) form.common_name = data.common_name;
    
  } catch (e) { 
    alert("Scan failed. Please check the domain/port."); 
  } finally { 
    scanning.value = false; 
  }
};
</script>