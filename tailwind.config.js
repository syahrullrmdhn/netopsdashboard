/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    "./node_modules/flowbite/**/*.js"
  ],

  theme: {
    extend: {
      fontFamily: {
        // Tambahkan fallback 'sans-serif' di setelah 'Mulish'
        sans: ['Mulish', 'sans-serif'],
      },
    },
  },

  plugins: [
    require('@tailwindcss/forms'),
    require('flowbite/plugin')
  ],
}
