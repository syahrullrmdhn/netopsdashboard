// webpack.mix.js
const mix = require('laravel-mix');

mix
  // Compile JS
  .js('resources/js/app.js', 'public/js')
  // Compile CSS + PostCSS (Tailwind, Autoprefixer)
  .postCss('resources/css/app.css', 'public/css', [
    require('tailwindcss'),
    require('autoprefixer'),
  ])
  // Aktifkan versioning untuk cache-busting di production
  .version();
