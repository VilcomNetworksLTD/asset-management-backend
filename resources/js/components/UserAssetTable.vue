<template>
  <div class="bg-white rounded shadow-lg border-t-4 border-[#3c8dbc]">
    <div class="p-4 border-b bg-gray-50">
      <h3 class="font-bold text-[#1e3a8a] text-sm uppercase">My Equipment</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-100 border-b text-[10px] uppercase text-gray-500 font-black">
          <tr>
            <th class="p-4">Asset Name</th>
            <th class="p-4">Serial Number</th>
            <th class="p-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="asset in assets" :key="asset.id" class="hover:bg-blue-50/50">
            <td class="p-4 font-bold text-gray-700">{{ asset.name }}</td>
            <td class="p-4 font-mono text-xs text-gray-400">{{ asset.serial }}</td>
            <td class="p-4 text-xs">
              <span class="bg-green-100 text-green-700 px-2 py-1 rounded-md font-bold uppercase text-[9px]">In Use</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps(['assets']);
</script>