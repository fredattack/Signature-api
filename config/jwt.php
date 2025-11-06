<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign JWT tokens
    |
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign JWT tokens
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Access Token TTL
    |--------------------------------------------------------------------------
    |
    | Time to live for access tokens in seconds (default: 15 minutes)
    |
    */
    'access_token_ttl' => env('JWT_ACCESS_TOKEN_TTL', 900),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Time to live for refresh tokens in seconds (default: 30 days)
    |
    */
    'refresh_token_ttl' => env('JWT_REFRESH_TOKEN_TTL', 2592000),
];
