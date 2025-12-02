import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/app/theme.css',
                'resources/css/filament/guest/theme.css'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Asset optimization settings
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                // Manual chunk splitting for better caching
                manualChunks: {
                    vendor: ['alpinejs'],
                    filament: ['@filamentphp/forms', '@filamentphp/tables', '@filamentphp/notifications'],
                },
                // Asset naming for cache busting
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
                        return `images/[name]-[hash].${ext}`;
                    }
                    if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
                        return `fonts/[name]-[hash].${ext}`;
                    }
                    return `assets/[name]-[hash].${ext}`;
                },
            },
        },
        // Chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Asset inlining threshold
        assetsInlineLimit: 4096,
        // Source maps for production debugging (optional)
        sourcemap: process.env.NODE_ENV === 'development',
        // CSS code splitting
        cssCodeSplit: true,
        // Asset optimization
        assetsDir: 'assets',
    },
    // CSS optimization
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                additionalData: `@import "resources/css/variables.scss";`,
            },
        },
    },
    // Server configuration for development
    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
        },
    },
    // Optimization settings
    optimizeDeps: {
        include: [
            'alpinejs',
            '@alpinejs/persist',
            '@alpinejs/focus',
            '@alpinejs/collapse',
        ],
    },
    // Define global constants
    define: {
        __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
    },
});
