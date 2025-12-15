import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0', // Bind to all interfaces for Docker
        port: 5173,
        watch: { usePolling: true }, // Critical for Docker file watching
        hmr: {
            host: 'localhost', // Use localhost for HMR so browser can connect
        },
    },
});