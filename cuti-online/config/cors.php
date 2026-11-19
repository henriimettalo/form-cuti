<?php

$origins = array_map('trim', explode(',', (string) env('API_CORS_ORIGINS', '')));

return [
    'paths' => ['api/v1/*'],
    'allowed_methods' => ['GET', 'HEAD', 'POST'],
    'allowed_origins' => array_values(array_filter(
        $origins,
        static fn (string $origin): bool => $origin !== '' && ! str_contains($origin, '*'),
    )),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
