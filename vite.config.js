import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    // Tarayıcının erişebileceği host (0.0.0.0 hot dosyasına yazılmamalı)
    const hmrHost = env.VITE_DEV_SERVER_HOST || process.env.VITE_DEV_SERVER_HOST || '127.0.0.1';
    const hmrPort = Number(env.VITE_DEV_SERVER_PORT || process.env.VITE_DEV_SERVER_PORT || 5174);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port: hmrPort,
            strictPort: true,
            cors: true,
            origin: `http://${hmrHost}:${hmrPort}`,
            hmr: {
                host: hmrHost,
                port: hmrPort,
                clientPort: hmrPort,
            },
            watch: {
                ignored: ['**/storage/framework/views/**', '**/database/data/**'],
            },
        },
    };
});
