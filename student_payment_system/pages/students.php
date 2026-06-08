<?php
// ============================================================
// pages/students.php  –  Student Management (List + Delete)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Students');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$message = '';
$msgType = 'success';

// ── Handle Delete ────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $pdo  = getDBConnection();
        // Check if student has payments first
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE student_id = :id");
        $stmt->execute([':id' => $deleteId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $message = 'Cannot delete student with existing payment records.';
            $msgType = 'danger';
        } else {
            $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);
            $name = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);

            logAction('DELETE_STUDENT', 'students', $deleteId, "Student '$name' deleted");
            $message = "Student '$name' has been deleted successfully.";
        }
    } catch (PDOException $e) {
        $message = 'Error deleting student. They may have related records.';
        $msgType = 'danger';
    }
}

$search   = trim($_GET['search'] ?? '');
$students = getAllStudents($search);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
    <?= h($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Header ── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Student Management</h5>
        <p class="text-muted small mb-0">Manage all enrolled students and their payment status</p>
    </div>
    <a href="<?= BASE_URL ?>/pages/add_student.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Add Student
    </a>
</div>

<!-- ── Search ── -->
<div class="card table-card mb-3 p-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control" style="max-width:320px"
               placeholder="Search by name, code, or program..."
               value="<?= h($search) ?>">
        <button type="submit" class="btn btn-outline-primary">
            <i class="bi bi-search"></i>
        </button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/pages/students.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- ── Table ── -->
<div class="card table-card">
    <div class="card-header bg-white py-3 px-4">
        <span class="fw-semibold">
            <i class="bi bi-people me-2 text-primary"></i>
            All Students
            <span class="badge bg-secondary ms-2"><?= count($students) ?></span>
        </span>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                        No students found.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($students as $i => $s):
                    $pct = $s['total_fees'] > 0
                        ? round(($s['total_paid'] / $s['total_fees']) * 100)
                        : 0;
                    $barColor = $pct >= 100 ? 'success' : ($pct >= 50 ? 'primary' : 'warning');
                ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td><code class="text-primary"><?= h($s['student_code']) ?></code></td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= $s['id'] ?>"
                           class="text-dark fw-semibold text-decoration-none">
                            <?= h($s['full_name']) ?>
                        </a>
                    </td>
                    <td><small class="text-muted"><?= h($s['program']) ?></small></td>
                    <td class="fw-semibold"><?= formatMoney($s['total_fees']) ?></td>
                    <td class="text-success fw-semibold"><?= formatMoney($s['total_paid']) ?></td>
                    <td class="<?= $s['remaining_balance'] > 0 ? 'text-danger' : 'text-success' ?> fw-semibold">
                        <?= formatMoney($s['remaining_balance']) ?>
                    </td>
                    <td style="min-width:100px">
                        <div class="progress" title="<?= $pct ?>% paid">
                            <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $pct ?>%</small>
                    </td>
                    <td>
                        <?php
                        $statusColors = ['active'=>'success','inactive'=>'secondary','graduated'=>'info'];
                        $color = $statusColors[$s['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> status-badge text-capitalize">
                            <?= h($s['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/pages/student_detail.php?id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/edit_student.php?id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/add_payment.php?student_id=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-success" title="Add Payment">
                                <i class="bi bi-cash-coin"></i>
                            </a>
                            <?php if ($s['remaining_balance'] <= 0): ?>
                            <?php else: ?>
                            <a href="?delete=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               title="Delete"
                               onclick="return confirm('Delete student <?= h(addslashes($s['full_name'])) ?>? This action cannot be undone.')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
