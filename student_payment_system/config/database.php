<?php
// ============================================================
// config/database.php  —  Single source of truth for DB + URL
// ============================================================

// ✅ Change this if your folder name is different
define('BASE_URL', '/student_payment_system');

define('DB_HOST',    'localhost');
define('DB_NAME',    'student_payment_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die('<div style="font-family:Arial;padding:30px;color:#c62828;background:#fff0f0;border:1px solid #e57373;border-radius:8px;margin:30px">
                 <strong>Database Connection Failed</strong><br><br>
                 Make sure:<br>
                 1. MySQL is running in XAMPP<br>
                 2. You imported database.sql in phpMyAdmin<br>
                 3. DB name is: <code>student_payment_db</code>
                 </div>');
        }
    }
    return $pdo;
}
