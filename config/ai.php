<?php

return [

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 90),
        'enabled' => (bool) env('GEMINI_ENABLED', true),
    ],

];
