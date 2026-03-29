<script setup>
import { computed } from 'vue';

// Get user role to conditionally show menu items
const user = JSON.parse(localStorage.getItem('user_data') || '{}');
const role = (user.role || '').toLowerCase();
const isHod = role === 'head_of_department' || role === 'hod';

// Example menu items. You should merge this with your existing sidebar items.
const menuItems = [
    { name: 'Dashboard', to: { name: 'dashboard-user' }, icon: 'fa-tachometer-alt' },
    { name: 'My Assets', to: { name: 'user-assets' }, icon: 'fa-box' },
    { name: 'My Tickets', to: { name: 'user-tickets' }, icon: 'fa-ticket-alt' },
];

const hodMenuItems = [
    { name: 'Department Assets', to: { name: 'HodDepartmentAssets' }, icon: 'fa-building' },
    { name: 'Manage Assets', to: { name: 'hod-assets-list' }, icon: 'fa-cogs' },
    { name: 'Manage Definitions', to: { name: 'hod-definitions' }, icon: 'fa-tags' },
];
</script>

<template>
    <!-- This is an example sidebar. Integrate the HOD menu into your actual UserLayout.vue component. -->
    <aside class="w-64 bg-gray-800 text-white p-4">
        <div class="mb-8">
            <h2 class="text-xl font-bold">User Portal</h2>
        </div>
        <nav>
            <ul>
                <!-- Standard User Links -->
                <li v-for="item in menuItems" :key="item.name" class="mb-1">
                    <router-link :to="item.to" class="flex items-center p-2 rounded hover:bg-gray-700 transition-colors" active-class="bg-blue-600">
                        <i :class="['fa', item.icon, 'w-6 text-center']"></i>
                        <span class="ml-3">{{ item.name }}</span>
                    </router-link>
                </li>

                <!-- HOD Specific Menu -->
                <template v-if="isHod">
                    <li class="mt-6 mb-2 px-2 text-xs uppercase text-gray-400 tracking-wider">HOD Menu</li>
                    <li v-for="item in hodMenuItems" :key="item.name" class="mb-1">
                        <router-link :to="item.to" class="flex items-center p-2 rounded hover:bg-gray-700 transition-colors" active-class="bg-blue-600">
                            <i :class="['fa', item.icon, 'w-6 text-center']"></i>
                            <span class="ml-3">{{ item.name }}</span>
                        </router-link>
                    </li>
                </template>
            </ul>
        </nav>
    </aside>
</template>