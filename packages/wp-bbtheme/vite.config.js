import { defineConfig } from "vite";

export default defineConfig({
    base: '',
    css: {
        preprocessorOptions: {
            scss: {
                api: "modern-compiler",
                silenceDeprecations: [
                    'import',
                    'global-builtin',
                    'mixed-decls'
                ],
                quietDeps: true
            },
        },
    },
    build: {
        // generate manifest.json in outDir
        manifest: true,
        sourcemap: true,
        rollupOptions: {
            // overwrite default .html entry
            input: {
                script: "src/js/main.js",
            },
        },
    },
    server: {
        hmr: false,
    },
});
