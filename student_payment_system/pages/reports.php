<?php
// ============================================================
// pages/reports.php  –  Reports & Analytics
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Reports');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();

// ── Full Student Balance Report ───────────────────────────────
$students = $pdo->query("SELECT * FROM v_student_balance ORDER BY remaining_balance DESC")->fetchAll();

// ── Overdue Installments ──────────────────────────────────────
$overdue = getOverdueStudents();

// ── Payments by Method ────────────────────────────────────────
$byMethod = $pdo->query(
    "SELECT payment_method,
            COUNT(*) AS cnt,
            SUM(amount) AS total
     FROM payments
     GROUP BY payment_method
     ORDER BY total DESC"
)->fetchAll();

// ── Monthly Collection ────────────────────────────────────────
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(payment_date,'%Y-%m') AS month,
            SUM(amount) AS total,
            COUNT(*) AS cnt
     FROM payments
     WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
     ORDER BY month"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h5 class="fw-bold mb-1">Reports & Analytics</h5>
    <p class="text-muted small mb-0">Financial overview and payment analytics</p>
</div>

<!-- ── Summary Row ── -->
<?php
$totalFees     = array_sum(array_column($students, 'total_fees'));
$totalPaid     = array_sum(array_column($students, 'total_paid'));
$totalRemaining= array_sum(array_column($students, 'remaining_balance'));
$fullyPaid     = count(array_filter($students, fn($s) => $s['remaining_balance'] <= 0));
$pct           = $totalFees > 0 ? round($totalPaid / $totalFees * 100, 1) : 0;
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="fs-2 fw-bold text-primary"><?= count($students) ?></div>
            <div class="text-muted small">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="fs-5 fw-bold text-success"><?= formatMoney($totalPaid) ?></div>
            <div class="text-muted small">Total Collected</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="fs-5 fw-bold text-danger"><?= formatMoney($totalRemaining) ?></div>
            <div class="text-muted small">Total Remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3 text-center">
            <div class="fs-2 fw-bold text-info"><?= $fullyPaid ?></div>
            <div class="text-muted small">Fully Paid</div>
        </div>
    </div>
    <div class="col-12">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="small fw-semibold">Overall Collection Rate</span>
                <span class="badge bg-primary"><?= $pct ?>%</span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success progress-bar-striped"
                     style="width:<?= $pct ?>%"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ── Payments by Method ── -->
    <div class="col-md-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2 text-primary"></i>By Payment Method</h6>
            </div>
            <div class="card-body">
                <?php foreach ($byMethod as $m):
                    $icons = ['cash'=>'bi-cash','bank_transfer'=>'bi-bank','check'=>'bi-file-earmark-text','online'=>'bi-globe'];
                    $icon  = $icons[$m['payment_method']] ?? 'bi-credit-card';
                    $mpct  = $totalPaid > 0 ? round($m['total'] / $totalPaid * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><i class="bi <?= $icon ?> me-1"></i><?= ucfirst(str_replace('_',' ',$m['payment_method'])) ?></span>
                        <span class="text-muted"><?= $m['cnt'] ?> · <?= formatMoney($m['total']) ?></span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar" style="width:<?= $mpct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Monthly Collection ── -->
    <div class="col-md-8">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-success"></i>Monthly Collections (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($monthly)): ?>
                    <p class="text-muted text-center">No data yet.</p>
                <?php else:
                    $maxMonthly = max(array_column($monthly, 'total'));
                    foreach ($monthly as $mo):
                        $bpct = $maxMonthly > 0 ? round($mo['total'] / $maxMonthly * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-semibold"><?= $mo['month'] ?></span>
                        <span class="text-success"><?= formatMoney($mo['total']) ?>
                            <span class="text-muted">(<?= $mo['cnt'] ?> payments)</span></span>
                    </div>
                    <div class="progress" style="height:10px">
                        <div class="progress-bar bg-success" style="width:<?= $bpct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Student Balance Table ── -->
<div class="card table-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2 text-primary"></i>Student Balance Report</h6>
        <small class="text-muted"><?= count($students) ?> students</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Student Name</th>
                    <th>Program</th>
                    <th>Total Fees</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Progress</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $i => $s):
                    $sp = $s['total_fees'] > 0 ? round($s['total_paid'] / $s['total_fees'] * 100) : 0;
                    $barCol = $sp >= 100 ? 'success' : ($sp >= 50 ? 'primary' : ($sp > 0 ? 'warning' : 'danger'));
                ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td><code class="text-primary small"><?= h($s['student_code']) ?></code></td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= $s['id'] ?>"
                           class="text-dark text-decoration-none fw-semibold">
                           <?= h($s['full_name']) ?>
                        </a>
                    </td>
                    <td><small class="text-muted"><?= h($s['program']) ?></small></td>
                    <td><?= formatMoney($s['total_fees']) ?></td>
                    <td class="text-success fw-semibold"><?= formatMoney($s['total_paid']) ?></td>
                    <td class="<?= $s['remaining_balance'] > 0 ? 'text-danger' : 'text-success' ?> fw-semibold">
                        <?= formatMoney($s['remaining_balance']) ?>
                    </td>
                    <td style="min-width:120px">
                        <div class="progress mb-1">
                            <div class="progress-bar bg-<?= $barCol ?>" style="width:<?= $sp ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $sp ?>%</small>
                    </td>
                    <td>
                        <?php if ($s['remaining_balance'] <= 0): ?>
                            <span class="badge bg-success-subtle text-success status-badge">Fully Paid</span>
                        <?php elseif ($s['total_paid'] == 0): ?>
                            <span class="badge bg-danger-subtle text-danger status-badge">No Payment</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning status-badge">Partial</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4">TOTAL</td>
                    <td><?= formatMoney($totalFees) ?></td>
                    <td class="text-success"><?= formatMoney($totalPaid) ?></td>
                    <td class="text-danger"><?= formatMoney($totalRemaining) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ── Overdue Installments ── -->
<?php if (!empty($overdue)): ?>
<div class="card table-card">
    <div class="card-header bg-white py-3 px-4" style="border-left:4px solid #dc3545">
        <h6 class="mb-0 fw-semibold text-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Overdue Installments
            <span class="badge bg-danger ms-2"><?= count($overdue) ?></span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Installment</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue as $o): ?>
                <tr>
                    <td>
                        <span class="fw-semibold"><?= h($o['full_name']) ?></span>
                        <div class="text-muted small"><?= h($o['student_code']) ?></div>
                    </td>
                    <td><small><?= h($o['phone'] ?: '—') ?></small></td>
                    <td><span class="badge bg-warning-subtle text-warning">Inst. #<?= $o['installment_no'] ?></span></td>
                    <td class="text-danger fw-semibold"><?= formatMoney($o['amount_due']) ?></td>
                    <td><small class="text-danger"><?= formatDate($o['due_date']) ?></small></td>
                    <td>
                        <span class="badge bg-danger">
                            <?= $o['days_overdue'] ?> days
                        </span>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/add_payment.php?student_id=<?= $o['student_code'] ?>"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-cash-coin"></i> Pay
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
