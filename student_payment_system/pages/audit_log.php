<?php
// ============================================================
// pages/audit_log.php  –  System Audit Trail (Admin Only)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
define('PAGE_TITLE', 'Audit Log');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();  // Admin only

$pdo = getDBConnection();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM transactions_log")->fetchColumn();
$pages = (int)ceil($total / $perPage);

$logs = $pdo->query(
    "SELECT tl.*, u.username
     FROM transactions_log tl
     LEFT JOIN users u ON u.id = tl.user_id
     ORDER BY tl.created_at DESC
     LIMIT $perPage OFFSET $offset"
)->fetchAll();

// Action badge colors
$actionColors = [
    'LOGIN'          => 'primary',
    'LOGOUT'         => 'secondary',
    'ADD_STUDENT'    => 'success',
    'EDIT_STUDENT'   => 'warning',
    'DELETE_STUDENT' => 'danger',
    'ADD_PAYMENT'    => 'info',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Audit Log</h5>
        <p class="text-muted small mb-0">Complete system activity trail — <?= $total ?> records</p>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $i => $log):
                    $col = $actionColors[$log['action']] ?? 'secondary';
                ?>
                <tr>
                    <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                    <td>
                        <span class="badge bg-<?= $col ?> status-badge">
                            <?= h($log['action']) ?>
                        </span>
                    </td>
                    <td><small class="fw-semibold"><?= h($log['username'] ?? 'System') ?></small></td>
                    <td><code class="small"><?= h($log['table_name']) ?></code></td>
                    <td><small class="text-muted"><?= $log['record_id'] ?? '—' ?></small></td>
                    <td><small><?= h($log['description']) ?></small></td>
                    <td><small class="text-muted font-monospace"><?= h($log['ip_address'] ?? '—') ?></small></td>
                    <td>
                        <small class="text-muted">
                            <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                        </small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
