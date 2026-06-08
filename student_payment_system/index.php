<?php
// ============================================================
// index.php  —  Login Page (تم التعريب)
// ============================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            require_once __DIR__ . '/includes/functions.php';
            logAction('LOGIN', 'users', $user['id'], "User {$user['username']} logged in");
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __t('sign_in') ?> | <?= __t('app_name') ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;direction:rtl}
.card{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:40px;width:100%;max-width:400px}
.logo{width:64px;height:64px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 18px}
h4{text-align:center;font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:6px}
.sub{text-align:center;color:#888;font-size:.85rem;margin-bottom:24px}
label{display:block;font-size:.85rem;font-weight:600;color:#444;margin-bottom:5px;text-align:right}
.field{display:flex;border:2px solid #dde1e7;border-radius:9px;overflow:hidden;margin-bottom:16px;background:#fff}
.field:focus-within{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.15)}
.icon{background:#f5f6fa;padding:0 13px;display:flex;align-items:center;color:#888;border-left:1px solid #dde1e7;font-size:15px}
.field input{flex:1;border:none;outline:none;padding:12px 13px;font-size:.92rem;color:#1a1a2e;background:#fff;text-align:right}
.field input::placeholder{color:#bbb}
.err{background:#fff0f0;border:1px solid #ffcccc;border-right:4px solid #e53935;border-radius:8px;padding:10px 13px;color:#c62828;font-size:.85rem;margin-bottom:16px;text-align:right}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:9px;font-size:.95rem;font-weight:700;cursor:pointer;transition:opacity .2s}
.btn:hover{opacity:.9}
.hint{margin-top:18px;background:#f5f6fa;border-radius:9px;padding:12px 14px;font-size:.8rem;color:#666;line-height:1.8;text-align:right}
code{background:#e8eaf6;color:#3949ab;padding:1px 6px;border-radius:4px;direction:ltr;display:inline-block}
</style>
</head>
<body>
<div class="card">
  <div class="logo">&#127891;</div>
  <h4>نظام مدفوعات الطلاب</h4>
  <p class="sub">تسجيل الدخول إلى حسابك</p>
  <?php if ($error): ?>
  <div class="err">&#9888; <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>اسم المستخدم</label>
    <div class="field">
      <span class="icon">&#128100;</span>
      <input type="text" name="username" placeholder="أدخل اسم المستخدم"
             value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             required autofocus>
    </div>
    <label>كلمة المرور</label>
    <div class="field">
      <span class="icon">&#128274;</span>
      <input type="password" name="password" placeholder="أدخل كلمة المرور" required>
    </div>
    <button type="submit" class="btn">تسجيل الدخول &larr;</button>
  </form>
  <div class="hint">
    <strong>بيانات الدخول الافتراضية:</strong><br>
    اسم المستخدم: <code>admin</code> &nbsp;|&nbsp; كلمة المرور: <code>admin123</code>
  </div>
</div>
</body>
</html>
