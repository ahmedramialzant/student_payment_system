<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
define('PAGE_TITLE', __t('dashboard'));

$stats      = getDashboardStats();
$overdue    = getOverdueStudents();
$pct        = $stats['totalFees'] > 0 ? round(($stats['totalPayments'] / $stats['totalFees']) * 100, 1) : 0;
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100 p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon" style="background:#e8f4fd"><i class="bi bi-people-fill text-primary"></i></div>
        <div><div class="fs-3 fw-bold"><?= $stats['totalStudents'] ?></div>
          <div class="text-muted small"><?= __t('active_students') ?></div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100 p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon" style="background:#e6f9f0"><i class="bi bi-cash-stack text-success"></i></div>
        <div><div class="fs-6 fw-bold text-success"><?= formatMoney($stats['totalPayments']) ?></div>
          <div class="text-muted small"><?= __t('total_collected') ?></div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100 p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon" style="background:#fff3e0"><i class="bi bi-hourglass-split text-warning"></i></div>
        <div><div class="fs-6 fw-bold text-warning"><?= formatMoney($stats['remaining']) ?></div>
          <div class="text-muted small"><?= __t('remaining_balance') ?></div></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100 p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon" style="background:#fde8e8"><i class="bi bi-exclamation-triangle-fill text-danger"></i></div>
        <div><div class="fs-3 fw-bold text-danger"><?= $stats['overdueCount'] ?></div>
          <div class="text-muted small"><?= __t('overdue_students') ?></div></div>
      </div>
    </div>
  </div>
</div>

<div class="card table-card mb-4 p-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 fw-semibold"><?= __t('collection_progress') ?></h6>
    <span class="badge bg-primary rounded-pill"><?= $pct ?>%</span>
  </div>
  <div class="progress">
    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:<?= $pct ?>%"></div>
  </div>
  <div class="d-flex justify-content-between mt-2">
    <small class="text-muted"><?= __t('collected') ?>: <?= formatMoney($stats['totalPayments']) ?></small>
    <small class="text-muted"><?= __t('total') ?>: <?= formatMoney($stats['totalFees']) ?></small>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card table-card">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i><?= __t('recent_payments') ?></h6>
        <a href="<?= BASE_URL ?>/pages/payments.php" class="btn btn-sm btn-outline-primary"><?= __t('view_all') ?></a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($stats['recentPayments'])): ?>
          <p class="text-muted text-center py-4 mb-0"><?= __t('no_payments_yet') ?></p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th><?= __t('full_name') ?></th>
              <th><?= __t('payment_amount') ?></th>
              <th><?= __t('date') ?></th>
              <th><?= __t('payment_method') ?></th>
            </tr></thead>
            <tbody>
              <?php foreach ($stats['recentPayments'] as $p): ?>
              <tr>
                <td><div class="fw-semibold small"><?= h($p['full_name']) ?></div>
                  <div class="text-muted" style="font-size:.78rem"><?= h($p['student_code']) ?></div></td>
                <td><span class="text-success fw-semibold"><?= formatMoney($p['amount']) ?></span></td>
                <td><small><?= formatDate($p['payment_date']) ?></small></td>
                <td><span class="badge bg-secondary rounded-pill text-capitalize small">
                  <?= __t(str_replace('_','_',$p['payment_method'])) ?>
                </span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card table-card">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-danger"></i><?= __t('overdue_installments') ?></h6>
        <span class="badge bg-danger rounded-pill"><?= count($overdue) ?></span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($overdue)): ?>
          <p class="text-muted text-center py-4 mb-0">
            <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i><?= __t('no_overdue') ?>
          </p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th><?= __t('full_name') ?></th>
              <th><?= __t('amount_due') ?></th>
              <th><?= __t('days_overdue') ?></th>
            </tr></thead>
            <tbody>
              <?php foreach (array_slice($overdue,0,8) as $o): ?>
              <tr>
                <td><div class="fw-semibold small"><?= h($o['full_name']) ?></div>
                  <small class="text-muted"><?= h($o['student_code']) ?></small></td>
                <td><span class="text-danger small fw-semibold"><?= formatMoney($o['amount_due']) ?></span></td>
                <td><span class="badge bg-danger-subtle text-danger small"><?= $o['days_overdue'] ?> <?= isAr()?'يوم':'d' ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card stat-card p-3">
      <h6 class="mb-3 fw-semibold text-muted"><?= __t('quick_actions') ?></h6>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/pages/add_student.php" class="btn btn-primary">
          <i class="bi bi-person-plus me-1"></i><?= __t('add_student') ?>
        </a>
        <a href="<?= BASE_URL ?>/pages/add_payment.php" class="btn btn-success">
          <i class="bi bi-plus-circle me-1"></i><?= __t('record_payment') ?>
        </a>
        <a href="<?= BASE_URL ?>/pages/reports.php" class="btn btn-outline-secondary">
          <i class="bi bi-file-earmark-bar-graph me-1"></i><?= __t('reports') ?>
        </a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
