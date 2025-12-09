<?php
class Security
{
    // CSRF Protection
    public static function generateCSRFToken()
    {
        self::startSecureSession(); // Pastikan session sudah dimulai
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token)
    {
        self::startSecureSession(); // Pastikan session sudah dimulai
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception("CSRF token validation failed");
        }
        return true;
    }

    // Start secure session dengan pengecekan
    public static function startSecureSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            // Set session security parameters SEBELUM session_start()
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);

            // Hanya set cookie_secure jika menggunakan HTTPS
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }

            // Start session
            session_start();

            // Regenerate session ID untuk mencegah fixation
            if (!isset($_SESSION['initialized'])) {
                session_regenerate_id(true);
                $_SESSION['initialized'] = true;
            }
        }
    }

    // Rate Limiting
    public static function checkRateLimit($key, $limit = 10, $period = 60)
    {
        self::startSecureSession(); // Pastikan session sudah dimulai

        $ip = $_SERVER['REMOTE_ADDR'];
        $cacheKey = "rate_limit_{$key}_{$ip}";

        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'count' => 1,
                'timestamp' => time()
            ];
            return true;
        }

        $data = $_SESSION[$cacheKey];

        if (time() - $data['timestamp'] > $period) {
            $_SESSION[$cacheKey] = [
                'count' => 1,
                'timestamp' => time()
            ];
            return true;
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $data['count']++;
        $_SESSION[$cacheKey] = $data;
        return true;
    }

    // Input Sanitization
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }

        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $input;
    }

    // XSS Prevention
    public static function xssClean($string)
    {
        $string = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $string);
        $string = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $string);
        $string = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $string);
        $string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');
        $string = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $string);
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $string);
        $string = preg_replace('#</*\w+:\w[^>]*+>#i', '', $string);
        do {
            $old_string = $string;
            $string = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $string);
        } while ($old_string !== $string);

        return $string;
    }

    // SQL Injection Prevention
    public static function escapeSQL($connection, $string)
    {
        if (is_array($string)) {
            return array_map(function ($item) use ($connection) {
                return self::escapeSQL($connection, $item);
            }, $string);
        }

        return $connection->real_escape_string($string);
    }

    // File Upload Validation
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], $maxSize = 5242880)
    {
        $errors = [];

        // Check file exists
        if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
            return [true, null]; // No file uploaded is acceptable
        }

        // Check upload error
        if ($file['error'] != UPLOAD_ERR_OK) {
            $errors[] = "Upload error: " . self::getUploadError($file['error']);
            return [false, $errors];
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = "File terlalu besar. Maksimal " . ($maxSize / 1024 / 1024) . "MB";
        }

        // Get file extension
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Check allowed extensions
        if (!in_array($fileExt, $allowedTypes)) {
            $errors[] = "Tipe file tidak diizinkan. Hanya: " . implode(', ', $allowedTypes);
        }

        // MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime, array_values($allowedMimes))) {
            $errors[] = "Tipe MIME file tidak valid";
        }

        // File name sanitization
        $safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $safeFileName = time() . '_' . $safeFileName;

        if (!empty($errors)) {
            return [false, $errors];
        }

        return [true, $safeFileName];
    }

    private static function getUploadError($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (form)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    // Password Hashing
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    // Redirect with message
    public static function redirect($url, $message = null, $type = 'success')
    {
        self::startSecureSession(); // Pastikan session sudah dimulai

        if ($message) {
            $_SESSION['flash_message'] = [
                'text' => $message,
                'type' => $type
            ];
        }
        header("Location: $url");
        exit();
    }

    // Get flash message
    public static function getFlashMessage()
    {
        self::startSecureSession(); // Pastikan session sudah dimulai

        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}