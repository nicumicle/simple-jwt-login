<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

function simple_jwt_login_autoloader($class)
{
    $namespaces = [
        [
            'class' => 'SimpleJWTLogin\\',
            'namespace' => 'SimpleJWTLogin\\',
            'directory' => __DIR__ . '/src/'
        ],
    ];

    $namespaceMap = null;
    foreach ($namespaces as $oneNamespace) {
        if (strpos($class, $oneNamespace['class']) !== false) {
            $namespaceMap = [
                $oneNamespace['namespace'] => $oneNamespace['directory']
            ];
        }
    }

    if (!empty($namespaceMap)) {
        foreach ($namespaceMap as $prefix => $dir) {
            $path = str_replace($prefix, $dir, $class);
            $path = str_replace('\\', '/', $path);
            $path = $path . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
}

spl_autoload_register('simple_jwt_login_autoloader');
