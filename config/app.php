<?php
// Application Configuration

define('APP_NAME', 'Agenda Pimpinan v2.0');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development'); // development, testing, production

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../temp/logs/error.log');
}

// Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Application URLs
define('BASE_URL', 'http://localhost/agenda-pimpinan/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Security
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('LOGIN_ATTEMPT_LIMIT', 5);
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Database (will be loaded from database.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dbagenda');
define('DB_CHARSET', 'utf8mb4');