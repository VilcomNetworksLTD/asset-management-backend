<template>
  <div class="base-card" :class="{ 'hover-effect': hover }">
    <slot></slot>
  </div>
</template>

<script setup>
defineProps({ hover: Boolean });
</script>

<style scoped>
.base-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
}
.hover-effect:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  border-color: #3b82f6;
}
</style>