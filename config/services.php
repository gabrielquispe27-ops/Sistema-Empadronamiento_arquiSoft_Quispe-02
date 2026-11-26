<?php

declare(strict_types=1);

return [
    'reniec' => [
        'token' => env('RENIEC_API_TOKEN'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
    ],
];
