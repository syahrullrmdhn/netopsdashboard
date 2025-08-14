<?php

return [
    'base'   => rtrim(env('OBSERVIUM_BASE', ''), '/'),
    'user'   => env('OBSERVIUM_USER'),
    'pass'   => env('OBSERVIUM_PASS'),
    'verify' => filter_var(env('OBSERVIUM_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),
];
