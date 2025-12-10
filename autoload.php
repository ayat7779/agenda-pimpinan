<?php
// Simple Autoloader

spl_autoload_register(function ($class_name) {
    // Convert namespace to file path
    $class_name = str_replace('\\', '/', $class_name);
    
    // Possible locations
    $locations = [
        __DIR__ . '/models/' . $class_name . '.php',
        __DIR__ . '/controllers/' . $class_name . '.php',
        __DIR__ . '/lib/' . $class_name . '.php',
        __DIR__ . '/helpers/' . $class_name . '.php',
        __DIR__ . '/middlewares/' . $class_name . '.php',
    ];
    
    foreach ($locations as $location) {
        if (file_exists($location)) {
            require_once $location;
            return;
        }
    }
});

// Load configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Load helper functions
require_once __DIR__ . '/helpers/functions.php';