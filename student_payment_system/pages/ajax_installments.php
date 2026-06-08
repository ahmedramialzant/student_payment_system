<?php
// ============================================================
// pages/ajax_installments.php  –  AJAX: Get Installments
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');


require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if (!$studentId) {
    echo json_encode([]);
    exit;
}

$pdo  = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT id, installment_no, amount_due, due_date, status
     FROM installments
     WHERE student_id = :id
       AND status IN ('pending','partial','overdue')
     ORDER BY installment_no"
);
$stmt->execute([':id' => $studentId]);
echo json_encode($stmt->fetchAll());
