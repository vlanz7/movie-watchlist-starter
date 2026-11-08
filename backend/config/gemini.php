<?php

return [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'fallback_model' => env('GEMINI_FALLBACK_MODEL'),
    'thinking_level' => env('GEMINI_THINKING_LEVEL', 'low'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
];