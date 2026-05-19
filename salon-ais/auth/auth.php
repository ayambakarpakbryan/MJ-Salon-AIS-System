<?php
// ============================================================
// MJ Salon AIS - Authentication Helper
// auth/auth.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * INTERNAL CONTROL: Require a logged-in user.
 * Redirects to login if not authenticated.
 */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * INTERNAL CONTROL: Require admin role.
 * Redirects to dashboard with error if not admin.
 */
function require_admin(): void {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = 'Akses ditolak. Hak akses admin diperlukan.';
        header('Location: ' . base_url('staff/transaction.php'));
        exit;
    }
}

/**
 * Check if current user is admin.
 */
function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current logged-in user's name.
 */
function current_user(): string {
    return $_SESSION['name'] ?? 'Unknown';
}

/**
 * Build absolute URL from relative path within the project.
 */
function base_url(string $path = ''): string {
    // Works for both Laragon and XAMPP
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Find project root folder name
    $parts  = explode('/', trim($script, '/'));
    $root   = $parts[0] ?? 'salon-ais';
    return '/' . $root . '/' . ltrim($path, '/');
}

/**
 * Flash message helpers
 */
function set_flash(string $type, string $msg): void {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg']  = $msg;
}

function get_flash(): array {
    $f = [
        'type' => $_SESSION['flash_type'] ?? '',
        'msg'  => $_SESSION['flash_msg']  ?? '',
    ];
    unset($_SESSION['flash_type'], $_SESSION['flash_msg']);
    return $f;
}
