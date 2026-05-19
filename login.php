<?php
// ============================================================
// MJ Salon AIS - Login Page
// login.php
// ============================================================
require_once __DIR__ . '/auth/auth.php';

// Already logged in? Redirect based on role
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php'); exit;
    } else {
        header('Location: staff/transaction.php'); exit;
    }
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — MJ Salon Information System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root {
    --rose:   #c9636a;
    --gold:   #d4a84b;
    --dark:   #1a1218;
    --cream:  #fdf8f2;
    --muted:  #6b5c64;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark);
    font-family: 'DM Sans', sans-serif;
    position: relative;
    overflow: hidden;
  }
  /* Decorative background pattern */
  body::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
      radial-gradient(circle at 20% 20%, rgba(201,99,106,0.15) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(212,168,75,0.12) 0%, transparent 50%);
    pointer-events: none;
  }
  .login-wrapper {
    display: flex;
    width: 860px;
    max-width: 98vw;
    min-height: 520px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.5);
    position: relative;
    z-index: 1;
  }
  /* Left decorative panel */
  .login-panel {
    width: 40%;
    background: linear-gradient(160deg, var(--rose) 0%, #8b1f2f 100%);
    padding: 50px 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }
  .login-panel::after {
    content: '';
    position: absolute;
    bottom: -60px; right: -60px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
  }
  .login-panel::before {
    content: '';
    position: absolute;
    top: -40px; left: -40px;
    width: 150px; height: 150px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
  }
  .salon-logo {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 2.4rem;
    line-height: 1.1;
    margin-bottom: 10px;
    position: relative;
  }
  .salon-logo span { color: var(--gold); }
  .panel-subtitle {
    color: rgba(255,255,255,0.75);
    font-size: 0.82rem;
    font-weight: 300;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 32px;
  }
  .panel-features { list-style: none; padding: 0; margin: 0; }
  .panel-features li {
    color: rgba(255,255,255,0.85);
    font-size: 0.82rem;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .panel-features li i { color: var(--gold); font-size: 1rem; }
  /* Right form panel */
  .login-form-area {
    width: 60%;
    background: var(--cream);
    padding: 50px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .form-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    color: var(--dark);
    margin-bottom: 6px;
  }
  .form-sub {
    color: var(--muted);
    font-size: 0.85rem;
    margin-bottom: 28px;
  }
  .form-label {
    font-size: 0.78rem;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 6px;
  }
  .form-control {
    background: #fff;
    border: 1.5px solid #e8ddd6;
    border-radius: 10px;
    padding: 11px 16px;
    font-size: 0.9rem;
    color: var(--dark);
    transition: border-color .2s, box-shadow .2s;
  }
  .form-control:focus {
    border-color: var(--rose);
    box-shadow: 0 0 0 3px rgba(201,99,106,0.12);
    outline: none;
  }
  .input-icon-wrap {
    position: relative;
  }
  .input-icon-wrap i {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--muted); font-size: 1rem;
  }
  .input-icon-wrap .form-control { padding-left: 40px; }
  .btn-login {
    background: linear-gradient(135deg, var(--rose), #a0303a);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-size: 0.92rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    width: 100%;
    margin-top: 6px;
    transition: opacity .2s, transform .1s;
    cursor: pointer;
  }
  .btn-login:hover { opacity: .9; transform: translateY(-1px); }
  .btn-login:active { transform: translateY(0); }
  .alert-login {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 10px;
    color: #b91c1c;
    font-size: 0.84rem;
    padding: 10px 14px;
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
  }
  .demo-accounts {
    margin-top: 24px;
    padding: 14px;
    background: #f5ede6;
    border-radius: 10px;
    font-size: 0.78rem;
    color: var(--muted);
  }
  .demo-accounts strong { color: var(--dark); }
  .demo-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 4px;
  }
  .badge-admin { background: #fde68a; color: #92400e; }
  .badge-staff { background: #d1fae5; color: #065f46; }
  @media (max-width: 640px) {
    .login-panel { display: none; }
    .login-form-area { width: 100%; padding: 36px 24px; }
    .login-wrapper { border-radius: 16px; }
  }
</style>
</head>
<body>

<div class="login-wrapper">
  <!-- Left Decorative Panel -->
  <div class="login-panel">
    <div class="salon-logo">MJ<br><span>Salon</span></div>
    <div class="panel-subtitle">Sistem Informasi Akuntansi</div>
    <ul class="panel-features">
      <li><i class="bi bi-journal-check"></i> Jurnal Akuntansi Otomatis</li>
      <li><i class="bi bi-graph-up-arrow"></i> Laporan Keuangan</li>
      <li><i class="bi bi-shield-check"></i> Kontrol Internal</li>
      <li><i class="bi bi-people"></i> Hak Akses Berbasis Peran</li>
      <li><i class="bi bi-receipt"></i> Cetak Struk</li>
    </ul>
  </div>

  <!-- Right Form Area -->
  <div class="login-form-area">
    <div class="form-title">Selamat Datang</div>
    <div class="form-sub">Masuk ke sistem MJ Salon</div>

    <?php if ($error): ?>
    <div class="alert-login">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form action="process/process_login.php" method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <div class="input-icon-wrap">
          <i class="bi bi-person"></i>
          <input type="text" name="username" class="form-control"
                 placeholder="Masukkan username" autocomplete="username" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-icon-wrap">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" class="form-control"
                 placeholder="Masukkan password" autocomplete="current-password" required>
        </div>
      </div>
      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
      </button>
    </form>

    <div class="demo-accounts">
      <strong>Akun Demo</strong><br>
      <div class="mt-2">
        <i class="bi bi-person-fill-gear"></i>
        <strong>admin</strong> / <code>password</code>
        <span class="demo-badge badge-admin">Admin</span>
      </div>
      <div class="mt-1">
        <i class="bi bi-person-badge"></i>
        <strong>sarah</strong> / <code>password</code>
        <span class="demo-badge badge-staff">Staff</span>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
