import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        {
            name: 'php-reload',
            handleHotUpdate({ file, server }) {
                if (file.endsWith('.php')) {
                    server.ws.send({ type: 'full-reload' });
                }
            },
        },
    ],
    build: {
        // generate .vite/manifest.json in outDir
        manifest: true,
        rollupOptions: {
            // overwrite default .html entry
            input: [
                'backend/js/main.js',
                'frontend/css/styles.css'
            ],
        },
    },
    server: {
        // origin is required for PHP integration
        origin: 'http://localhost:5173',
        cors: true,
    },
});
