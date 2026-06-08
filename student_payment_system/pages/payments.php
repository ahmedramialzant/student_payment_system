<?php
// ============================================================
// pages/payments.php  –  All Payments List
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Payments');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();

// Filters
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterMethod  = trim($_GET['method'] ?? '');
$dateFrom      = trim($_GET['date_from'] ?? '');
$dateTo        = trim($_GET['date_to']   ?? '');

$sql    = "SELECT p.*, s.full_name, s.student_code, u.full_name AS received_by_name,
                  i.installment_no
           FROM payments p
           JOIN students s ON s.id = p.student_id
           JOIN users    u ON u.id = p.received_by
           LEFT JOIN installments i ON i.id = p.installment_id
           WHERE 1=1";
$params = [];

if ($filterStudent) {
    $sql .= " AND p.student_id = :sid";
    $params[':sid'] = $filterStudent;
}
if ($filterMethod) {
    $sql .= " AND p.payment_method = :method";
    $params[':method'] = $filterMethod;
}
if ($dateFrom) {
    $sql .= " AND p.payment_date >= :df";
    $params[':df'] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND p.payment_date <= :dt";
    $params[':dt'] = $dateTo;
}
$sql .= " ORDER BY p.payment_date DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totalAmount = array_sum(array_column($payments, 'amount'));

// Students for filter dropdown
$allStudents = $pdo->query("SELECT id, student_code, full_name FROM students ORDER BY full_name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Payment Records</h5>
        <p class="text-muted small mb-0">All payment transactions</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/add_payment.php" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i>Record Payment
    </a>
</div>

<!-- ── Filters ── -->
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Student</label>
                <select name="student_id" class="form-select form-select-sm">
                    <option value="">All Students</option>
                    <?php foreach ($allStudents as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterStudent == $s['id'] ? 'selected':'' ?>>
                        <?= h($s['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <?php foreach (['cash','bank_transfer','check','online'] as $m): ?>
                    <option value="<?= $m ?>" <?= $filterMethod === $m ? 'selected':'' ?>>
                        <?= ucfirst(str_replace('_', ' ', $m)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= h($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= h($dateTo) ?>">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Summary ── -->
<div class="alert alert-success py-2 d-flex gap-3 mb-3">
    <span><i class="bi bi-receipt me-1"></i><strong><?= count($payments) ?></strong> payments found</span>
    <span>|</span>
    <span>Total: <strong><?= formatMoney($totalAmount) ?></strong></span>
</div>

<!-- ── Table ── -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Installment</th>
                    <th>Reference</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>No payments found.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($payments as $i => $p): ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= $p['student_id'] ?>"
                           class="text-dark fw-semibold text-decoration-none">
                            <?= h($p['full_name']) ?>
                        </a>
                        <div class="text-muted" style="font-size:.78rem"><?= h($p['student_code']) ?></div>
                    </td>
                    <td>
                        <span class="text-success fw-bold"><?= formatMoney($p['amount']) ?></span>
                    </td>
                    <td><small><?= formatDate($p['payment_date']) ?></small></td>
                    <td>
                        <?php
                        $methodIcons = [
                            'cash' => 'bi-cash', 'bank_transfer' => 'bi-bank',
                            'check' => 'bi-file-text', 'online' => 'bi-globe'
                        ];
                        $icon = $methodIcons[$p['payment_method']] ?? 'bi-currency-dollar';
                        ?>
                        <span class="badge bg-secondary-subtle text-secondary text-capitalize small">
                            <i class="bi <?= $icon ?> me-1"></i>
                            <?= str_replace('_', ' ', $p['payment_method']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($p['installment_no']): ?>
                            <span class="badge bg-primary-subtle text-primary small">
                                Inst. #<?= $p['installment_no'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">General</span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= h($p['reference_no'] ?: '—') ?></small></td>
                    <td><small class="text-muted"><?= h($p['received_by_name']) ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($payments)): ?>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="2">Total (<?= count($payments) ?> payments)</td>
                    <td class="text-success"><?= formatMoney($totalAmount) ?></td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
