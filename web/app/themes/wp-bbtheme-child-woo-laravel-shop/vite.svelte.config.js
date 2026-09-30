import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
  plugins: [svelte()],
  build: {
    emptyOutDir: false,
    lib: {
      entry: 'resources/svelte/CatalogStatus.svelte',
      name: 'WPBBCatalogStatus',
      formats: ['es'],
      fileName: () => 'svelte-catalog-status.js'
    },
    outDir: 'assets/js'
  }
});
