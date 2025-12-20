import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import { imagetools } from "vite-imagetools";

export default defineConfig({
    publicDir: 'public',
    plugins: [
        vue(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
        imagetools({
            defaultDirectives: (url) => {
                // Auto-optimize images dengan format modern
                if (url.searchParams.has('w') || url.searchParams.has('h')) {
                    return new URLSearchParams({
                        format: 'webp',
                        quality: '85',
                    });
                }
                return new URLSearchParams();
            },
        }),
    ],
    build: {
        ssrManifest: true,
        target: 'es2015', // Modern browsers untuk reduce polyfills
        minify: 'terser', // Terser lebih agresif dari esbuild
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log di production
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'],
            },
        },
        rollupOptions: {
            input: "/resources/js/app.js",
            output: {
                manualChunks: {
                    // Core vendor (always loaded)
                    'vue-vendor': ['vue', '@inertiajs/vue3', 'pinia'],
                    
                    // Utils (small, frequently used)
                    'utils': ['axios', 'dayjs'],
                    
                    // Charts (lazy loaded)
                    'charts': ['html-to-image', 'html2canvas'],
                    
                    // Heavy UI libs (lazy loaded for admin pages only)
                    'vue-select': ['vue-select'],
                    'v-calendar': ['v-calendar'],
                    'cropper': ['vue-advanced-cropper'],
                    'tel-input': ['vue3-tel-input'],
                    'draggable': ['vue-draggable-next'],
                },
                // Optimize chunk file names
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        // Increase chunk size warning limit (jika perlu)
        chunkSizeWarningLimit: 600,
    },
    ssr: {
        noExternal: ["vue-router"], // penting buat routing server side
    },
});
