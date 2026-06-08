<?php
// ============================================================
// includes/functions.php
// Utility / Business Logic Functions
// ============================================================

require_once __DIR__ . '/../config/database.php';
if (!function_exists('__t')) require_once __DIR__ . '/../config/app.php';

// ── Sanitization ─────────────────────────────────────────────

/**
 * Sanitize output for HTML display (prevent XSS)
 */
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// formatMoney() is defined in config/app.php (supports AR/EN currency)

/**
 * Format date to readable format
 */
function formatDate(string $date): string {
    if (empty($date)) return 'N/A';
    return date('d M Y', strtotime($date));
}

// ── Audit Trail ──────────────────────────────────────────────

/**
 * Log every important action into transactions_log
 */
function logAction(
    string $action,
    string $tableName,
    int    $recordId,
    string $description,
    ?int   $userId = null
): void {
    $pdo    = getDBConnection();
    $userId = $userId ?? ($_SESSION['user_id'] ?? null);
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $sql = "INSERT INTO transactions_log
                (user_id, action, table_name, record_id, description, ip_address)
            VALUES (:user_id, :action, :table_name, :record_id, :description, :ip)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'     => $userId,
        ':action'      => $action,
        ':table_name'  => $tableName,
        ':record_id'   => $recordId,
        ':description' => $description,
        ':ip'          => $ip,
    ]);
}

// ── Student Functions ─────────────────────────────────────────

/**
 * Generate next student code automatically
 */
function generateStudentCode(): string {
    $pdo  = getDBConnection();
    $year = date('Y');
    $sql  = "SELECT COUNT(*) as cnt FROM students WHERE YEAR(enrollment_date) = :year";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':year' => $year]);
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf('STU-%d-%03d', $year, $count);
}

/**
 * Get all students with balance summary
 */
function getAllStudents(string $search = ''): array {
    $pdo = getDBConnection();
    $sql = "SELECT * FROM v_student_balance";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE full_name LIKE :search OR student_code LIKE :search2 OR program LIKE :search3";
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single student with balance
 */
function getStudentById(int $id): ?array {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM v_student_balance WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get total paid for a student
 */
function getStudentTotalPaid(int $studentId): float {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id = :id");
    $stmt->execute([':id' => $studentId]);
    return (float)$stmt->fetchColumn();
}

/**
 * Auto-generate installments when a student is added
 * Splits total_fees equally into $count installments
 * First due date = enrollment_date + 1 month
 */
function createInstallments(int $studentId, float $totalFees, string $enrollmentDate, int $count = 3): void {
    $pdo           = getDBConnection();
    $installAmount = round($totalFees / $count, 2);
    $baseDate      = new DateTime($enrollmentDate);

    for ($i = 1; $i <= $count; $i++) {
        $baseDate->modify('+1 month');
        $dueDate = $baseDate->format('Y-m-d');

        $sql  = "INSERT INTO installments (student_id, installment_no, amount_due, due_date)
                 VALUES (:sid, :no, :amount, :due)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sid'    => $studentId,
            ':no'     => $i,
            ':amount' => $installAmount,
            ':due'    => $dueDate,
        ]);
    }
}

// ── Payment Functions ─────────────────────────────────────────

/**
 * Register a payment with business rule validation
 * Returns ['success'=>bool, 'message'=>string]
 */
function addPayment(
    int    $studentId,
    float  $amount,
    string $paymentDate,
    string $method,
    string $referenceNo,
    int    $receivedBy,
    string $notes = '',
    ?int   $installmentId = null
): array {
    $pdo = getDBConnection();

    // --- Rule: amount must be positive ---
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
    }

    // --- Rule: cannot pay more than remaining balance ---
    $stmt = $pdo->prepare("SELECT total_fees - COALESCE(SUM(p.amount),0)
                           FROM students s
                           LEFT JOIN payments p ON p.student_id = s.id
                           WHERE s.id = :id
                           GROUP BY s.id, s.total_fees");
    $stmt->execute([':id' => $studentId]);
    $remaining = (float)$stmt->fetchColumn();

    if ($amount > $remaining + 0.01) {   // 0.01 tolerance for float
        return [
            'success' => false,
            'message' => 'Payment amount (' . formatMoney($amount) .
                         ') exceeds remaining balance (' . formatMoney($remaining) . ').'
        ];
    }

    // --- Insert payment ---
    $sql = "INSERT INTO payments
                (student_id, installment_id, amount, payment_date, payment_method, reference_no, received_by, notes)
            VALUES (:sid, :iid, :amount, :pdate, :method, :ref, :recv, :notes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sid'    => $studentId,
        ':iid'    => $installmentId,
        ':amount' => $amount,
        ':pdate'  => $paymentDate,
        ':method' => $method,
        ':ref'    => $referenceNo,
        ':recv'   => $receivedBy,
        ':notes'  => $notes,
    ]);
    $paymentId = (int)$pdo->lastInsertId();

    // --- Auto-update installment status if linked ---
    if ($installmentId !== null) {
        updateInstallmentStatus($installmentId);
    }

    logAction('ADD_PAYMENT', 'payments', $paymentId,
        "Payment of " . formatMoney($amount) . " added for student ID $studentId");

    return ['success' => true, 'message' => 'Payment recorded successfully.', 'id' => $paymentId];
}

/**
 * Recalculate and update installment status based on payments
 */
function updateInstallmentStatus(int $installmentId): void {
    $pdo = getDBConnection();

    // Get installment data
    $stmt = $pdo->prepare("SELECT amount_due FROM installments WHERE id = :id");
    $stmt->execute([':id' => $installmentId]);
    $inst = $stmt->fetch();
    if (!$inst) return;

    // Sum payments linked to this installment
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE installment_id = :id");
    $stmt->execute([':id' => $installmentId]);
    $paid = (float)$stmt->fetchColumn();

    if ($paid <= 0) {
        $newStatus = 'pending';
    } elseif ($paid >= $inst['amount_due']) {
        $newStatus = 'paid';
    } else {
        $newStatus = 'partial';
    }

    $stmt = $pdo->prepare("UPDATE installments SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $installmentId]);
}

// ── Dashboard Stats ───────────────────────────────────────────

function getDashboardStats(): array {
    $pdo = getDBConnection();

    // Total students
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();

    // Total payments collected
    $totalPayments = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();

    // Total fees billed
    $totalFees = (float)$pdo->query("SELECT COALESCE(SUM(total_fees),0) FROM students")->fetchColumn();

    // Remaining balance
    $remaining = $totalFees - $totalPayments;

    // Overdue students (students with at least one overdue/pending installment past due date)
    $overdueCount = (int)$pdo->query(
        "SELECT COUNT(DISTINCT student_id) FROM installments
         WHERE status IN ('pending','overdue') AND due_date < CURDATE()"
    )->fetchColumn();

    // Recent payments (last 5)
    $recentPayments = $pdo->query(
        "SELECT p.*, s.full_name, s.student_code
         FROM payments p
         JOIN students s ON s.id = p.student_id
         ORDER BY p.created_at DESC LIMIT 5"
    )->fetchAll();

    return compact('totalStudents','totalPayments','totalFees','remaining','overdueCount','recentPayments');
}

/**
 * Get overdue students list
 */
function getOverdueStudents(): array {
    $pdo = getDBConnection();
    return $pdo->query("SELECT * FROM v_overdue_installments")->fetchAll();
}

/**
 * Get payments for a specific student
 */
function getStudentPayments(int $studentId): array {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT p.*, u.full_name AS received_by_name,
                i.installment_no
         FROM payments p
         JOIN users u ON u.id = p.received_by
         LEFT JOIN installments i ON i.id = p.installment_id
         WHERE p.student_id = :id
         ORDER BY p.payment_date DESC"
    );
    $stmt->execute([':id' => $studentId]);
    return $stmt->fetchAll();
}

/**
 * Get installments for a specific student
 */
function getStudentInstallments(int $studentId): array {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT i.*,
                COALESCE(SUM(p.amount), 0) AS amount_paid
         FROM installments i
         LEFT JOIN payments p ON p.installment_id = i.id
         WHERE i.student_id = :id
         GROUP BY i.id
         ORDER BY i.installment_no"
    );
    $stmt->execute([':id' => $studentId]);
    return $stmt->fetchAll();
}
