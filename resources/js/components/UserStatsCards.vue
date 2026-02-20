<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-[#00c0ef] text-white p-5 rounded shadow-md flex justify-between items-center border-b-4 border-black/10">
      <div>
        <div class="text-3xl font-bold">{{ assetsCount }}</div>
        <div class="text-[11px] uppercase font-black">My Assigned Assets</div>
      </div>
      <i class="fa fa-laptop text-4xl opacity-20"></i>
    </div>

    <div class="bg-[#dd4b39] text-white p-5 rounded shadow-md flex justify-between items-center border-b-4 border-black/10">
      <div>
        <div class="text-3xl font-bold">{{ ticketsCount }}</div>
        <div class="text-[11px] uppercase font-black">Open Tickets</div>
      </div>
      <i class="fa fa-exclamation-triangle text-4xl opacity-20"></i>
    </div>

    <div class="bg-[#f39c12] text-white p-5 rounded shadow-md flex justify-between items-center border-b-4 border-black/10 cursor-pointer hover:opacity-90">
      <div>
        <div class="text-xl font-black italic uppercase">New Report</div>
        <div class="text-[11px] font-medium opacity-80">Request Assistance</div>
      </div>
      <i class="fa fa-plus-circle text-4xl opacity-30"></i>
    </div>
  </div>
</template>

<script setup>
defineProps(['assetsCount', 'ticketsCount']);
</script>