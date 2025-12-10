<?php
// Di awal index.php
error_log("=== Accessing index.php ===");
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("SESSION: " . print_r($_SESSION, true));

// Cek jika redirect sudah terjadi berkali-kali
if (!isset($_SESSION['access_count'])) {
    $_SESSION['access_count'] = 1;
} else {
    $_SESSION['access_count']++;
}

if ($_SESSION['access_count'] > 5) {
    die("Redirect loop detected! Count: " . $_SESSION['access_count']);
}

// ... kode aplikasi Anda/ File: index.php (Main entry point)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$conn = DatabaseConfig::getConnection();

// Initialize Auth
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/models/Permission.php';
$auth = new Auth($conn);
$permission = new Permission($conn);

// Routing
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Define allowed pages for each role
$allowedPages = [
    'super_admin' => ['dashboard', 'agenda', 'tindaklanjut', 'users', 'profile', 'settings'],
    'admin' => ['dashboard', 'agenda', 'tindaklanjut', 'users', 'profile'],
    'pimpinan' => ['dashboard', 'agenda', 'tindaklanjut', 'profile'],
    'staff' => ['dashboard', 'agenda', 'profile']
];

// Check if user is logged in
if (!$auth->isLoggedIn() && $page !== 'login') {
    header('Location: views/auth/login.php');
    exit;
}

// Check page access
if ($auth->isLoggedIn()) {
    $userRole = $auth->getUserRole();
    
    if (!in_array($page, $allowedPages[$userRole] ?? [])) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

// Load controller based on page
$controllerFile = __DIR__ . "/controllers/" . ucfirst($page) . "Controller.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = ucfirst($page) . "Controller";
    
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        
        // Call action method
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            // Default to index
            $controller->index();
        }
    } else {
        die("Controller class $controllerClass not found.");
    }
} else {
    // If no controller, load view directly
    $viewFile = __DIR__ . "/views/$page/$action.php";
    
    if (file_exists($viewFile)) {
        // Check if view requires auth
        if (!$auth->isLoggedIn() && $page !== 'login') {
            header('Location: login.php');
            exit;
        }
        
        include $viewFile;
    } else {
        // 404
        header("HTTP/1.0 404 Not Found");
        include __DIR__ . '/views/errors/404.php';
    }
}