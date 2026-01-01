import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Optimize chunk size
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs', '@alpinejs/persist'],
                    'vendor': ['axios'],
                },
            },
        },
        // Increase chunk size warning limit
        chunkSizeWarningLimit: 600,
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
                drop_debugger: true,
            },
        },
        // Enable CSS code splitting
        cssCodeSplit: true,
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', '@alpinejs/persist', 'axios'],
    },
});
