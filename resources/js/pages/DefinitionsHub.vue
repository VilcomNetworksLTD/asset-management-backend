<template>
  <div class="space-y-8">
    <div class="mb-6">
      <h1 class="text-3xl font-black text-slate-800 tracking-tight">Definitions <span class="text-vilcom-blue">Hub</span></h1>
      <p class="text-sm text-gray-400 font-bold mt-1 uppercase tracking-[0.2em] leading-relaxed">Manage asset categories, locations, and organizational parameters.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="flex border-b border-gray-200">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentTab = tab.id"
          class="px-8 py-4 text-sm font-black uppercase tracking-wider transition-all duration-300 flex items-center gap-3"
          :class="currentTab === tab.id 
            ? 'text-vilcom-blue border-b-3 border-vilcom-blue bg-blue-50/30' 
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
        >
          <component :is="tab.icon" class="size-5" />
          {{ tab.label }}
        </button>
      </div>

      <div class="p-6">
        <div v-show="currentTab === 'categories'" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
          <CategorySettings />
        </div>

        <div v-show="currentTab === 'locations'" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
          <LocationSettings />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Tags, MapPin } from 'lucide-vue-next';
import CategorySettings from './CategorySettings.vue';
import LocationSettings from './LocationSettings.vue';

const currentTab = ref('categories');

const tabs = [
  { id: 'categories', label: 'Categories', icon: Tags },
  { id: 'locations', label: 'Locations', icon: MapPin },
];
</script>

<style scoped>
.border-b-3 {
  border-bottom-width: 3px;
}
</style>