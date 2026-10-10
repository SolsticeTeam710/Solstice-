import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/pelanggan.css',
                'resources/js/pelanggan.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '192.168.1.7',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
