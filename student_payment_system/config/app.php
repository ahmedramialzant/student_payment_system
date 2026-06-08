<?php
// ============================================================
// config/app.php  —  App settings + Language system
// ============================================================

define('CURRENCY_AR', 'شيكل');
define('CURRENCY_EN', 'ILS');

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar','en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$GLOBALS['lang'] = $_SESSION['lang'] ?? 'ar';

$GLOBALS['t'] = require __DIR__ . '/../lang/' . $GLOBALS['lang'] . '.php';

function __t(string $key): string {
    return $GLOBALS['t'][$key] ?? $key;
}

function lang(): string {
    return $GLOBALS['lang'];
}

function isAr(): bool {
    return $GLOBALS['lang'] === 'ar';
}

// ✅ Renamed from dir() to htmlDir() — dir() is a built-in PHP function
function getDir(): string {
    return isAr() ? 'rtl' : 'ltr';
}

function formatMoney(float $amount): string {
    $formatted = number_format($amount, 2);
    return isAr()
        ? $formatted . ' ' . CURRENCY_AR
        : $formatted . ' ' . CURRENCY_EN;
}

function langUrl(string $targetLang): string {
    $params         = $_GET;
    $params['lang'] = $targetLang;
    unset($params['delete'], $params['success']);
    return '?' . http_build_query($params);
}
