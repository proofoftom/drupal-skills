import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: 'src/main.js',
      name: 'GroupAiPmKanban',
      fileName: () => 'kanban.js',
      formats: ['iife'],
    },
    outDir: 'dist',
    emptyOutDir: true,
    minify: 'terser',
    rollupOptions: {
      external: ['vue'],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
  },
});
