<?php
// ============================================================
// includes/auth.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
    ];
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}
