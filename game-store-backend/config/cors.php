<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],


    'allowed_origins' => [
        'http://games:5173',
        'http://localhost:5173',
        'https://game-45428688-fe94e.web.app',
        'https://game-45428688-fe94e.firebaseapp.com',
    ],


    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,


    'supports_credentials' => true,

];
