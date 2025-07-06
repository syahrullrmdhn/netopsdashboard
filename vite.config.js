import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
      ],
      refresh: true,
      // jika Anda menggunakan buildDirectory, seharusnya default sudah 'build'
      // buildDirectory: 'build',
    }),
  ],
  // Override build options supaya manifest di-root outDir
  build: {
    outDir: 'public/build',      // tempel hasil build di public/build
    assetsDir: 'assets',         // ini default
    manifest: true,              // generate manifest
    // Vite 6+ menaruh manifest di .vite/manifest.json — kita pindahkan:
    manifestFileName: 'manifest.json',
    rollupOptions: {
      // jika ingin custom asset names dst
    }
  },
});
