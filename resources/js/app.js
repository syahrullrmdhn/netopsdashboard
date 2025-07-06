/* --------------------------------------------------------------------------
 |  Global JS entry-point (bundled by Vite)
 *-------------------------------------------------------------------------- */

/* 1. Alpine.js ------------------------------------------------------------ */
import Alpine from 'alpinejs'

window.Alpine = Alpine
Alpine.start()

/* 2. Flatpickr ------------------------------------------------------------ */
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'          // <— optional theme: import your own

document.addEventListener('alpine:init', () => {
    /* Inisialisasi semua input yang memiliki class .datetime-local */
    flatpickr('.datetime-local', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        time_24hr: true,
        allowInput: true,
        minuteIncrement: 1,
    })
})

/* 3. Tambahan script lain bisa di-import di sini -------------------------- */
// import Chart from 'chart.js/auto'
// ...
