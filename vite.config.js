import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // Added this line
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(), // Added this line to handle your .vue files
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js', // Helps resolve your imports like '@/components/...'
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});