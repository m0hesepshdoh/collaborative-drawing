<?php
return [
    'path' => env('ADMIN_PATH', 'admin-panel-xyz'),
    'password' => env('ADMIN_PASSWORD', 'changeme'),
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', env('ADMIN_ALLOWED_IPS', '127.0.0.1,::1'))))),
];
