<?php

return [
    'require_admin_mfa' => env('REQUIRE_ADMIN_MFA', env('APP_ENV') === 'production'),
    'scan_uploads' => env('SCAN_UPLOADS', env('APP_ENV') === 'production'),
    'clamd_host' => env('CLAMD_HOST', 'clamav'),
    'clamd_port' => (int) env('CLAMD_PORT', 3310),
];
