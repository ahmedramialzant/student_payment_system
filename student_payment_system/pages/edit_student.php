<?php
// ============================================================
// pages/edit_student.php
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Edit Student');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: ' . BASE_URL . '/pages/students.php'); exit; }

$pdo    = getDBConnection();
$stmt   = $pdo->prepare("SELECT * FROM students WHERE id = :id");
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();
if (!$student) { header('Location: ' . BASE_URL . '/pages/students.php'); exit; }

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['full_name']   ?? '');
    $email      = trim($_POST['email']       ?? '');
    $phone      = trim($_POST['phone']       ?? '');
    $program    = trim($_POST['program']     ?? '');
    $status     = trim($_POST['status']      ?? 'active');
    $notes      = trim($_POST['notes']       ?? '');

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($program))  $errors[] = 'Program is required.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
                          $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        $sql = "UPDATE students SET full_name=:name, email=:email, phone=:phone,
                    program=:program, status=:status, notes=:notes
                WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'    => $fullName,
            ':email'   => $email,
            ':phone'   => $phone,
            ':program' => $program,
            ':status'  => $status,
            ':notes'   => $notes,
            ':id'      => $id,
        ]);
        logAction('EDIT_STUDENT', 'students', $id, "Student '$fullName' updated");
        $success = 'Student record updated successfully.';
        // Re-fetch updated record
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $student = $stmt->fetch();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?= h($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-4 gap-2">
    <a href="<?= BASE_URL ?>/pages/students.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">Edit Student</h5>
        <small class="text-muted">Code: <code><?= h($student['student_code']) ?></code></small>
    </div>
</div>

<div class="col-lg-7">
    <form method="POST" class="form-card">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= h($student['full_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['active','inactive','graduated'] as $s): ?>
                    <option value="<?= $s ?>" <?= $student['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= h($student['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= h($student['phone']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Program <span class="text-danger">*</span></label>
                <input type="text" name="program" class="form-control"
                       value="<?= h($student['program']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= h($student['notes']) ?></textarea>
            </div>
        </div>

        <!-- Read-only financial info -->
        <div class="mt-3 p-3 rounded" style="background:#f8f9fa">
            <small class="text-muted">
                <strong>Note:</strong> Total fees and enrollment date cannot be changed after creation
                to maintain financial record integrity.
                Total Fees: <strong><?= formatMoney($student['total_fees']) ?></strong> |
                Enrolled: <strong><?= formatDate($student['enrollment_date']) ?></strong>
            </small>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i>Save Changes
            </button>
            <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                View Details
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
