<?php
// ============================================================
// pages/student_detail.php  –  Full Student Profile
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Student Details');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: ' . BASE_URL . '/pages/students.php'); exit; }

$student = getStudentById($id);
if (!$student) { header('Location: ' . BASE_URL . '/pages/students.php'); exit; }

$payments     = getStudentPayments($id);
$installments = getStudentInstallments($id);
$pct          = $student['total_fees'] > 0
    ? round(($student['total_paid'] / $student['total_fees']) * 100)
    : 0;

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/students.php">Students</a></li>
        <li class="breadcrumb-item active"><?= h($student['full_name']) ?></li>
    </ol>
</nav>

<!-- ── Student Header Card ── -->
<div class="card table-card p-4 mb-4">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div class="d-flex gap-3 align-items-center">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fs-2 fw-bold"
                 style="width:64px;height:64px;flex-shrink:0">
                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
            </div>
            <div>
                <h4 class="mb-1 fw-bold"><?= h($student['full_name']) ?></h4>
                <code class="text-primary"><?= h($student['student_code']) ?></code>
                <span class="badge bg-success-subtle text-success ms-2 text-capitalize"><?= h($student['status']) ?></span>
                <div class="text-muted small mt-1">
                    <i class="bi bi-mortarboard me-1"></i><?= h($student['program']) ?>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/pages/add_payment.php?student_id=<?= $id ?>"
               class="btn btn-success"><i class="bi bi-cash-coin me-1"></i>Record Payment</a>
            <a href="<?= BASE_URL ?>/pages/edit_student.php?id=<?= $id ?>"
               class="btn btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="row g-3 mt-3">
        <div class="col-4">
            <div class="text-muted small">Total Fees</div>
            <div class="fs-5 fw-bold"><?= formatMoney($student['total_fees']) ?></div>
        </div>
        <div class="col-4">
            <div class="text-muted small">Total Paid</div>
            <div class="fs-5 fw-bold text-success"><?= formatMoney($student['total_paid']) ?></div>
        </div>
        <div class="col-4">
            <div class="text-muted small">Remaining</div>
            <div class="fs-5 fw-bold <?= $student['remaining_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                <?= formatMoney($student['remaining_balance']) ?>
            </div>
        </div>
        <div class="col-12">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Payment Progress</small>
                <small class="fw-semibold"><?= $pct ?>%</small>
            </div>
            <div class="progress">
                <div class="progress-bar bg-<?= $pct >= 100 ? 'success' : ($pct >= 50 ? 'primary' : 'warning') ?>"
                     style="width:<?= $pct ?>%"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- ── Installments ── -->
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-calendar-check me-2 text-primary"></i>Installment Schedule
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $inst):
                            $statusColors = [
                                'pending' => 'warning',
                                'paid'    => 'success',
                                'overdue' => 'danger',
                                'partial' => 'info',
                            ];
                            $col = $statusColors[$inst['status']] ?? 'secondary';
                            $isOverdue = $inst['status'] === 'pending'
                                && strtotime($inst['due_date']) < time();
                        ?>
                        <tr>
                            <td><?= $inst['installment_no'] ?></td>
                            <td>
                                <small class="<?= $isOverdue ? 'text-danger fw-semibold' : '' ?>">
                                    <?= formatDate($inst['due_date']) ?>
                                    <?php if ($isOverdue): ?>
                                        <i class="bi bi-exclamation-circle ms-1"></i>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><?= formatMoney($inst['amount_due']) ?></td>
                            <td>
                                <span class="text-success small">
                                    <?= formatMoney($inst['amount_paid']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $col ?>-subtle text-<?= $col ?> status-badge text-capitalize">
                                    <?= $inst['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Payment History ── -->
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-receipt me-2 text-success"></i>Payment History
                </h6>
                <span class="badge bg-secondary"><?= count($payments) ?></span>
            </div>
            <?php if (empty($payments)): ?>
                <p class="text-muted text-center py-5 mb-0">No payments recorded yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Ref#</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><small><?= formatDate($pay['payment_date']) ?></small></td>
                            <td><span class="text-success fw-semibold"><?= formatMoney($pay['amount']) ?></span></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-capitalize small">
                                    <?= str_replace('_',' ', $pay['payment_method']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= h($pay['reference_no'] ?: '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
