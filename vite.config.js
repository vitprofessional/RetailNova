import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const assetPath = new URL(env.ASSET_URL || env.APP_URL || 'http://localhost', 'http://localhost')
        .pathname.replace(/\/$/, '');
    return {
        base: `${assetPath}/build/`,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/purchase-datatables.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
    };
});
