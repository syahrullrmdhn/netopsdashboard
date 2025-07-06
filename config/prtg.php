<?php
// config/prtg.php

return [
    'host'     => env('PRTG_HOST',''),
    'username' => env('PRTG_USER',''),
    'passhash' => env('PRTG_PASSHASH',''),
    // tambahkan report_id
    'report_id'=> env('PRTG_REPORT_ID',''),
];
