<?php
// ============================================================
// pages/add_student.php  –  Add New Student
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Add Student');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$errors  = [];
$success = '';

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    $fullName       = trim($_POST['full_name']       ?? '');
    $email          = trim($_POST['email']           ?? '');
    $phone          = trim($_POST['phone']           ?? '');
    $program        = trim($_POST['program']         ?? '');
    $enrollDate     = trim($_POST['enrollment_date'] ?? '');
    $totalFees      = floatval($_POST['total_fees']  ?? 0);
    $numInstallments= (int)($_POST['num_installments'] ?? 3);
    $notes          = trim($_POST['notes']           ?? '');

    // Validation
    if (empty($fullName))   $errors[] = 'Full name is required.';
    if (empty($program))    $errors[] = 'Program is required.';
    if (empty($enrollDate)) $errors[] = 'Enrollment date is required.';
    if ($totalFees <= 0)    $errors[] = 'Total fees must be greater than zero.';
    if ($numInstallments < 1 || $numInstallments > 12)
                            $errors[] = 'Installments must be between 1 and 12.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
                            $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        try {
            $pdo  = getDBConnection();
            $code = generateStudentCode();

            $sql = "INSERT INTO students
                        (student_code, full_name, email, phone, program, enrollment_date, total_fees, notes, created_by)
                    VALUES
                        (:code, :name, :email, :phone, :program, :enroll, :fees, :notes, :by)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':code'    => $code,
                ':name'    => $fullName,
                ':email'   => $email,
                ':phone'   => $phone,
                ':program' => $program,
                ':enroll'  => $enrollDate,
                ':fees'    => $totalFees,
                ':notes'   => $notes,
                ':by'      => $_SESSION['user_id'],
            ]);
            $studentId = (int)$pdo->lastInsertId();

            // Auto-generate installments
            createInstallments($studentId, $totalFees, $enrollDate, $numInstallments);

            logAction('ADD_STUDENT', 'students', $studentId,
                "Student '$fullName' (Code: $code) added with $numInstallments installments");

            header("Location: {$_SERVER['PHP_SELF']}?success=1&code=" . urlencode($code));
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$isSuccess = isset($_GET['success']) && isset($_GET['code']);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($isSuccess): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle-fill me-2"></i>
    Student added successfully! Code: <strong><?= h($_GET['code']) ?></strong>
    <a href="<?= BASE_URL ?>/pages/students.php" class="alert-link ms-2">View All Students</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="<?= BASE_URL ?>/pages/students.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">Add New Student</h5>
        <small class="text-muted">Fill in student details. Installments will be created automatically.</small>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" novalidate>
            <div class="form-card mb-3">
                <h6 class="fw-semibold mb-3 pb-2 border-bottom">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Personal Information
                </h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               placeholder="e.g. Ahmed Hassan Ali"
                               value="<?= h($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               placeholder="e.g. 0501234567"
                               value="<?= h($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="student@example.com"
                               value="<?= h($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Program / Department <span class="text-danger">*</span></label>
                        <input type="text" name="program" class="form-control"
                               placeholder="e.g. Computer Science"
                               list="programs-list"
                               value="<?= h($_POST['program'] ?? '') ?>" required>
                        <datalist id="programs-list">
                            <option value="Computer Science">
                            <option value="Business Administration">
                            <option value="Electrical Engineering">
                            <option value="Civil Engineering">
                            <option value="Medicine">
                            <option value="Law">
                        </datalist>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Any additional notes..."><?= h($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-card mb-3">
                <h6 class="fw-semibold mb-3 pb-2 border-bottom">
                    <i class="bi bi-cash-coin me-2 text-success"></i>Financial Details
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Enrollment Date <span class="text-danger">*</span></label>
                        <input type="date" name="enrollment_date" class="form-control"
                               value="<?= h($_POST['enrollment_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4">
<label class="form-label">إجمالي الرسوم (شيكل) <span class="text-danger">*</span></label>                        <input type="number" name="total_fees" class="form-control"
                               min="1" step="0.01"
                               placeholder="e.g. 15000"
                               value="<?= h($_POST['total_fees'] ?? '') ?>"
                               id="totalFeesInput" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Installments <span class="text-danger">*</span></label>
                        <select name="num_installments" class="form-select" id="numInstall">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"
                                <?= (($_POST['num_installments'] ?? 3) == $i) ? 'selected' : '' ?>>
                                <?= $i ?> installment<?= $i > 1 ? 's' : '' ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <!-- Live preview -->
                    <div class="col-12">
 <div class="alert alert-info py-2 mb-0" id="installPreview">
    قيمة كل قسط: <strong id="installAmt">-</strong> شيكل
</div>                      </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-person-plus me-1"></i>Add Student & Generate Installments
                </button>
                <a href="<?= BASE_URL ?>/pages/students.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── Help Card ── -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="background:#f8f9fa">
            <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle text-primary me-2"></i>How It Works</h6>
            <ol class="small text-muted mb-0 ps-3">
                <li class="mb-1">Fill in student's personal info</li>
                <li class="mb-1">Enter the total program fees</li>
                <li class="mb-1">Choose how many installments</li>
                <li class="mb-1">System auto-calculates each installment amount</li>
                <li class="mb-1">Due dates start 1 month after enrollment, monthly intervals</li>
                <li>A unique student code is generated automatically</li>
            </ol>
        </div>
    </div>
</div>

<script>
// Live installment preview
function updatePreview() {
    const fees = parseFloat(document.getElementById('totalFeesInput').value) || 0;
    const num  = parseInt(document.getElementById('numInstall').value) || 1;
    const amt  = fees > 0 ? (fees / num).toFixed(2) : '-';
    document.getElementById('installAmt').textContent = fees > 0 ? amt : '-';
}
document.getElementById('totalFeesInput').addEventListener('input', updatePreview);
document.getElementById('numInstall').addEventListener('change', updatePreview);
updatePreview();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
