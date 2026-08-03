<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | Access ve refresh token imzası için kullanılır. Boşsa APP_KEY kullanılır.
    | Üretimde ayrı bir JWT_SECRET tanımlamanız önerilir.
    |
    */

    'secret' => env('JWT_SECRET'),

    'algo' => env('JWT_ALGO', 'HS256'),

    'issuer' => env('JWT_ISSUER', 'basvuru360'),

    'access_ttl' => (int) env('JWT_ACCESS_TTL', 60), // dakika

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 60 * 24 * 14), // dakika (14 gün)

    'blacklist_enabled' => (bool) env('JWT_BLACKLIST_ENABLED', true),

];
