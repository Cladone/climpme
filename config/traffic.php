<?php

return [
    // Sign short-lived ingest JWTs for the front-end beacon
    'jwt_secret' => env('TRAFFIC_JWT_SECRET', null),
    'jwt_ttl_default' => env('TRAFFIC_JWT_TTL_DEFAULT', 86400), // 24h
];
