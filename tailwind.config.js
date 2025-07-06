/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    // Path utama untuk semua file view dan JavaScript Anda
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue", // Sertakan jika Anda menggunakan Vue.js

    // Path standar untuk view paginasi Laravel
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',

    // Path untuk Flowbite (jika Anda menggunakannya)
    "./node_modules/flowbite/**/*.js"
  ],

  theme: {
    extend: {
      fontFamily: {
        // Mengatur 'Raleway' sebagai font sans-serif utama untuk seluruh proyek
        sans: ['Raleway', 'ui-sans-serif', 'system-ui'],
      },
    },
  },

  plugins: [
    // Plugin yang sangat umum untuk styling form default
    require('@tailwindcss/forms'),

    // Plugin untuk Flowbite (jika Anda menggunakannya)
    require('flowbite/plugin')
  ],
}