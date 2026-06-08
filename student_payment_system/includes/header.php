<?php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', __t('dashboard'));
$user  = currentUser();
$isRtl = isAr();
?>
<!DOCTYPE html>
<html lang="<?= lang() ?>" dir="<?= getDir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(PAGE_TITLE) ?> | <?= __t('app_name') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<?php if ($isRtl): ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--sidebar-w:250px;--primary:#0d6efd;--sidebar-bg:#1a2035}
<?php if($isRtl): ?>body,*{font-family:'Segoe UI',Tahoma,'Arabic Typesetting',Arial,sans-serif!important}<?php endif; ?>
body{background:#f0f2f5}
#sidebar{width:var(--sidebar-w);height:100vh;position:fixed;top:0;<?= $isRtl?'right':'left' ?>:0;background:var(--sidebar-bg);color:#fff;z-index:1000;overflow-y:auto;display:flex;flex-direction:column}
#sidebar .brand{padding:20px 16px;font-size:1.1rem;font-weight:700;border-bottom:1px solid rgba(255,255,255,.1)}
#sidebar .nav-link{color:rgba(255,255,255,.75);padding:10px 16px;border-radius:6px;margin:2px 8px;font-size:.88rem;display:flex;align-items:center;gap:8px}
#sidebar .nav-link:hover,#sidebar .nav-link.active{color:#fff;background:var(--primary)}
#main-content{margin-<?= $isRtl?'right':'left' ?>:var(--sidebar-w);min-height:100vh}
.top-navbar{background:#fff;padding:12px 24px;border-bottom:1px solid #dee2e6;position:sticky;top:0;z-index:999}
.page-content{padding:24px}
.stat-card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07);transition:transform .2s}
.stat-card:hover{transform:translateY(-3px)}
.stat-card .icon{width:52px;height:52px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.table-card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden}
.table thead th{background:#f8f9fa;border-bottom:2px solid #dee2e6;font-weight:600;font-size:.82rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d}
.status-badge{font-size:.76rem;padding:4px 10px;border-radius:20px;font-weight:500}
.form-card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:28px}
.form-label{font-weight:500;font-size:.88rem;color:#495057}
.form-control,.form-select{border-radius:8px;border-color:#dee2e6;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 .2rem rgba(13,110,253,.15)}
.progress{height:8px;border-radius:10px}
.lang-btn{font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:20px;text-decoration:none;border:1.5px solid;transition:all .2s}
.lang-btn.active-lang{background:var(--primary);color:#fff!important;border-color:var(--primary)}
.lang-btn:not(.active-lang){color:var(--primary)!important;border-color:var(--primary);background:transparent}
.lang-btn:not(.active-lang):hover{background:var(--primary);color:#fff!important}
@media(max-width:768px){#sidebar{width:0}#main-content{margin:0}}
</style>
</head>
<body>
<nav id="sidebar">
  <div class="brand"><i class="bi bi-mortarboard-fill text-primary me-2"></i><?= $isRtl?'نظام الأقساط':'PayTrack' ?></div>
  <ul class="nav flex-column mt-2 flex-grow-1">
    <?php
    $pg   = basename($_SERVER['PHP_SELF']);
    $base = BASE_URL;
    $nav  = [
      ['dashboard.php','bi-speedometer2','dashboard'],
      ['students.php', 'bi-people-fill', 'students'],
      ['payments.php', 'bi-cash-stack',  'payments'],
      ['reports.php',  'bi-bar-chart-fill','reports'],
    ];
    foreach($nav as [$file,$icon,$key]):
    ?>
    <li class="nav-item">
      <a class="nav-link <?= $pg===$file?'active':'' ?>" href="<?= $base ?>/pages/<?= $file ?>">
        <i class="bi <?= $icon ?>"></i> <?= __t($key) ?>
      </a>
    </li>
    <?php endforeach; ?>
    <?php if($user['role']==='admin'): ?>
    <li class="nav-item mt-3 px-3"><small class="text-muted" style="font-size:.72rem"><?= __t('admin') ?></small></li>
    <li class="nav-item">
      <a class="nav-link <?= $pg==='audit_log.php'?'active':'' ?>" href="<?= $base ?>/pages/audit_log.php">
        <i class="bi bi-journal-text"></i> <?= __t('audit_log') ?>
      </a>
    </li>
    <?php endif; ?>
  </ul>
  <div class="p-3 border-top border-secondary">
    <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:rgba(255,255,255,.06)">
      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:32px;height:32px;font-size:.82rem">
        <?= strtoupper(mb_substr($user['full_name'],0,1)) ?>
      </div>
      <div style="overflow:hidden;flex:1">
        <div class="text-white small fw-semibold text-truncate"><?= h($user['full_name']) ?></div>
        <div class="text-muted" style="font-size:.72rem"><?= h($user['role']) ?></div>
      </div>
      <a href="<?= $base ?>/logout.php" class="text-danger" title="<?= __t('sign_out') ?>">
        <i class="bi bi-box-arrow-<?= $isRtl?'left':'right' ?> fs-5"></i>
      </a>
    </div>
  </div>
</nav>
<div id="main-content">
  <div class="top-navbar d-flex align-items-center justify-content-between gap-3">
    <h6 class="mb-0 fw-semibold text-dark text-truncate"><?= h(PAGE_TITLE) ?></h6>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <span class="text-muted small d-none d-md-inline"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?></span>
      <a href="<?= langUrl('ar') ?>" class="lang-btn <?= $isRtl?'active-lang':'' ?>">عربي</a>
      <a href="<?= langUrl('en') ?>" class="lang-btn <?= !$isRtl?'active-lang':'' ?>">EN</a>
      <a href="<?= $base ?>/logout.php" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-box-arrow-<?= $isRtl?'left':'right' ?> me-1"></i><?= __t('sign_out') ?>
      </a>
    </div>
  </div>
  <div class="page-content">
