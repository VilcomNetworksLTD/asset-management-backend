<template>
  <div id="app-root" class="min-h-screen bg-[#ecf0f5]">
    <router-view />
  </div>
</template>

<script setup>
// No changes needed here
</script>

<style>
/* 1. Removed 'scoped' so these resets apply to the entire page */
/* 2. Target the html and body to prevent white gaps at the edges */
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