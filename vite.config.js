import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.js',
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  },
  server: {
    hmr: {
      host: 'localhost',
    },
  },
})

