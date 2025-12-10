<?php
// File: helpers/functions.php

/**
 * Escape output untuk mencegah XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 */
function indonesianDate($dateString, $withTime = false) {
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $date = new DateTime($dateString);
    $day = $date->format('d');
    $month = $months[(int)$date->format('m') - 1];
    $year = $date->format('Y');
    
    $formatted = "$day $month $year";
    
    if ($withTime) {
        $time = $date->format('H:i');
        $formatted .= " $time";
    }
    
    return $formatted;
}

/**
 * Check permission untuk current user
 */
function can($permissionKey) {
    global $auth, $permission;
    
    if (!isset($auth) || !isset($permission)) {
        return false;
    }
    
    $role = $auth->getUserRole();
    return $permission->hasPermission($role, $permissionKey);
}

/**
 * Get role display name
 */
function roleDisplayName($role) {
    $roles = [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'pimpinan' => 'Pimpinan',
        'staff' => 'Staff'
    ];
    
    return $roles[$role] ?? $role;
}

/**
 * Generate breadcrumb
 */
function breadcrumb($items) {
    $html = '<nav class="breadcrumb">';
    $html .= '<ol>';
    
    foreach ($items as $key => $item) {
        if ($key === array_key_last($items)) {
            $html .= '<li class="active">' . e($item) . '</li>';
        } else {
            $html .= '<li><a href="#">' . e($item) . '</a></li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate random string
 */
function randomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get user initials for avatar
 */
function getUserInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= strtoupper($word[0]);
        if (strlen($initials) >= 2) break;
    }
    
    return $initials ?: '?';
}