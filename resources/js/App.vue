<template>
  <div id="app-root" class="min-h-screen bg-[#ecf0f5]">
    <router-view />
    <Toast ref="toastRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, provide } from 'vue';
import Toast from '@/components/Toast.vue';

const toastRef = ref(null);

provide('notify', {
  success: (msg) => toastRef.value?.add(msg, 'success'),
  error: (msg) => toastRef.value?.add(msg, 'error')
});

// Also expose to window for legacy code or non-setup scripts
onMounted(() => {
  window.vnlNotify = {
    success: (msg) => toastRef.value?.add(msg, 'success'),
    error: (msg) => toastRef.value?.add(msg, 'error')
  };
});
</script>

<style>

html, body {
  margin: 0;
  padding: 0;
  background-color: #ecf0f5; 
  height: 100%;
}

#app-root {
  width: 100%;
  min-height: 100vh;
}
</style>