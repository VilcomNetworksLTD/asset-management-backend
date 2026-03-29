/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                'vilcom-blue': '#1e40af',
                'vilcom-orange': '#f97316',
                'surface-bg': '#f8fafc',
            }
        },
    },
    plugins: [],
}