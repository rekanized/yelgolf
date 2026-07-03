<?php

$viewCacheContext = PHP_SAPI;

if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
    $processUser = posix_getpwuid(posix_geteuid())['name'] ?? null;

    if (is_string($processUser) && $processUser !== '') {
        $viewCacheContext .= '-'.$processUser;
    }
}

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views-'.$viewCacheContext)
    ),
];