<?php
// ============================================================
// pages/add_payment.php  –  Record a New Payment
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Record Payment');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getDBConnection();

// Pre-select student if coming from student page
$preStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Load all active students for dropdown
$allStudents = $pdo->query(
    "SELECT s.id, s.student_code, s.full_name,
            s.total_fees - COALESCE(SUM(p.amount),0) AS remaining
     FROM students s
     LEFT JOIN payments p ON p.student_id = s.id
     WHERE s.status = 'active'
     GROUP BY s.id, s.student_code, s.full_name, s.total_fees
     HAVING remaining > 0
     ORDER BY s.full_name"
)->fetchAll();

$errors  = [];
$success = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId      = (int)($_POST['student_id']      ?? 0);
    $installmentId  = !empty($_POST['installment_id']) ? (int)$_POST['installment_id'] : null;
    $amount         = floatval($_POST['amount']        ?? 0);
    $paymentDate    = trim($_POST['payment_date']      ?? '');
    $method         = trim($_POST['payment_method']    ?? 'cash');
    $referenceNo    = trim($_POST['reference_no']      ?? '');
    $notes          = trim($_POST['notes']             ?? '');

    if (!$studentId)        $errors[] = 'Please select a student.';
    if ($amount <= 0)       $errors[] = 'Payment amount must be greater than zero.';
    if (empty($paymentDate)) $errors[] = 'Payment date is required.';

    if (empty($errors)) {
        $result = addPayment(
            $studentId, $amount, $paymentDate, $method,
            $referenceNo, (int)$_SESSION['user_id'], $notes, $installmentId
        );
        if ($result['success']) {
            header("Location: {$_SERVER['PHP_SELF']}?success=1&student_id={$studentId}");
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

$isSuccess = isset($_GET['success']);
$sId = $preStudentId ?: ((int)($_POST['student_id'] ?? 0));

include __DIR__ . '/../includes/header.php';
?>

<?php if ($isSuccess): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle-fill me-2"></i>
    Payment recorded successfully!
    <?php if (isset($_GET['student_id'])): ?>
    <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= (int)$_GET['student_id'] ?>"
       class="alert-link">View Student</a>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong><i class="bi bi-exclamation-triangle me-2"></i>Error:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">Record New Payment</h5>
        <small class="text-muted">All amounts are validated against the student's remaining balance</small>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <form method="POST" novalidate id="paymentForm">
            <div class="form-card">
                <div class="row g-3">
                    <!-- Student Selector -->
                    <div class="col-12">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" id="studentSelect" required
                                onchange="loadInstallments(this.value)">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($allStudents as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                    data-remaining="<?= $s['remaining'] ?>"
                                <?= ($sId === $s['id']) ? 'selected' : '' ?>>
                                <?= h($s['student_code']) ?> – <?= h($s['full_name']) ?>
                                (Remaining: <?= formatMoney($s['remaining']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Remaining Balance Hint -->
                    <div class="col-12" id="balanceHint" style="display:none">
                        <div class="alert alert-info py-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Remaining balance: <strong id="remainingAmt">-</strong>
                        </div>
                    </div>

                    <!-- Installment Link (optional) -->
                    <div class="col-12">
                        <label class="form-label">Link to Installment (optional)</label>
                        <select name="installment_id" class="form-select" id="installmentSelect">
                            <option value="">-- General Payment (not linked) --</option>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="col-md-6">
                        <label class="form-label">Amount (SAR) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control"
                               min="0.01" step="0.01"
                               placeholder="0.00"
                               value="<?= h($_POST['amount'] ?? '') ?>"
                               id="amountInput" required>
                    </div>

                    <!-- Payment Date -->
                    <div class="col-md-6">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control"
                               value="<?= h($_POST['payment_date'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <?php
                            $methods = ['cash'=>'Cash','bank_transfer'=>'Bank Transfer',
                                        'check'=>'Check','online'=>'Online'];
                            foreach ($methods as $val => $label):
                            ?>
                            <option value="<?= $val ?>"
                                <?= (($_POST['payment_method'] ?? 'cash') === $val) ? 'selected':'' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reference Number -->
                    <div class="col-md-6">
                        <label class="form-label">Reference / Receipt No.</label>
                        <input type="text" name="reference_no" class="form-control"
                               placeholder="Optional receipt number"
                               value="<?= h($_POST['reference_no'] ?? '') ?>">
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Optional notes..."><?= h($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-cash-coin me-1"></i>Record Payment
                    </button>
                    <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="background:#f8f9fa">
            <h6 class="fw-semibold mb-2"><i class="bi bi-shield-check text-success me-2"></i>Payment Rules</h6>
            <ul class="small text-muted mb-0 ps-3">
                <li class="mb-1">Payment cannot exceed the remaining balance</li>
                <li class="mb-1">Linking to an installment updates its status automatically</li>
                <li class="mb-1">All payments are recorded with date and method</li>
                <li>Every payment is logged in the audit trail</li>
            </ul>
        </div>
    </div>
</div>

<script>
// PHP data for installment loading
const BASE_URL = '<?= BASE_URL ?>';

function loadInstallments(studentId) {
    const sel   = document.getElementById('installmentSelect');
    const hint  = document.getElementById('balanceHint');
    const remEl = document.getElementById('remainingAmt');
    const opt   = document.getElementById('studentSelect').options;
    const selected = Array.from(opt).find(o => o.value == studentId);

    if (!studentId) {
        sel.innerHTML = '<option value="">-- General Payment (not linked) --</option>';
        hint.style.display = 'none';
        return;
    }

    // Show remaining balance
    if (selected) {
        const rem = parseFloat(selected.dataset.remaining || 0);
        remEl.textContent = rem.toLocaleString('en-US', {minimumFractionDigits:2}) + ' SAR';
        hint.style.display = 'block';

        // Auto-fill amount field with remaining
        document.getElementById('amountInput').max = rem;
    }

    // Fetch pending installments via AJAX
    fetch(BASE_URL + '/pages/ajax_installments.php?student_id=' + studentId)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">-- General Payment (not linked) --</option>';
            data.forEach(i => {
                sel.innerHTML += `<option value="${i.id}">
                    #${i.installment_no} – Due: ${i.due_date} – ${parseFloat(i.amount_due).toLocaleString('en-US',{minimumFractionDigits:2})} SAR (${i.status})
                </option>`;
            });
        })
        .catch(() => {});
}

// Run on page load if student pre-selected
window.addEventListener('load', () => {
    const sel = document.getElementById('studentSelect');
    if (sel.value) loadInstallments(sel.value);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
