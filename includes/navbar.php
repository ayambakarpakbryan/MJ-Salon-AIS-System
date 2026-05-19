<?php
// ============================================================
// MJ Salon AIS - Shared Navbar / Sidebar
// includes/navbar.php
//
// Usage: require_once __DIR__ . '/../includes/navbar.php';
// Expects $page_title to be set before including.
// ============================================================

if (!isset($page_title)) $page_title = 'MJ Salon AIS';
$role       = $_SESSION['role']     ?? 'staff';
$user_name  = $_SESSION['name']     ?? 'User';
$base       = base_url();
$flash      = get_flash();

// Detect current page for nav highlighting
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — MJ Salon AIS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/custom.css') ?>" rel="stylesheet">
<style>
:root {
  --sidebar-bg:   #1a1218;
  --sidebar-w:    240px;
  --rose:         #c9636a;
  --gold:         #d4a84b;
  --cream:        #fdf8f2;
  --muted:        #6b5c64;
  --card-bg:      #ffffff;
  --page-bg:      #f5ede8;
}
body {
  font-family: 'DM Sans', sans-serif;
  background: var(--page-bg);
  margin: 0;
  min-height: 100vh;
}
/* ── Sidebar ─────────────────────────────────── */
.sidebar {
  position: fixed; top: 0; left: 0;
  width: var(--sidebar-w);
  height: 100vh;
  background: var(--sidebar-bg);
  display: flex; flex-direction: column;
  z-index: 1000;
  overflow-y: auto;
}
.sidebar-logo {
  padding: 28px 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sidebar-logo .brand-name {
  font-family: 'Playfair Display', serif;
  color: #fff;
  font-size: 1.5rem;
  line-height: 1;
}
.sidebar-logo .brand-name span { color: var(--gold); }
.sidebar-logo .brand-sub {
  font-size: 0.65rem;
  color: rgba(255,255,255,0.4);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-top: 4px;
}
.sidebar-section {
  padding: 16px 16px 4px;
  font-size: 0.62rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
}
.nav-item-link {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 20px;
  color: rgba(255,255,255,0.65);
  text-decoration: none;
  font-size: 0.86rem;
  font-weight: 400;
  border-radius: 0;
  transition: background .15s, color .15s;
  position: relative;
}
.nav-item-link i { font-size: 1rem; width: 18px; text-align: center; }
.nav-item-link:hover {
  background: rgba(255,255,255,0.06);
  color: #fff;
}
.nav-item-link.active {
  background: rgba(201,99,106,0.18);
  color: var(--rose);
}
.nav-item-link.active::before {
  content: '';
  position: absolute; left: 0; top: 0; bottom: 0;
  width: 3px; background: var(--rose);
  border-radius: 0 3px 3px 0;
}
.sidebar-user {
  margin-top: auto;
  padding: 16px 20px;
  border-top: 1px solid rgba(255,255,255,0.07);
  display: flex; align-items: center; gap: 10px;
}
.user-avatar {
  width: 34px; height: 34px;
  background: var(--rose);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 500; color: #fff; font-size: 0.85rem;
  flex-shrink: 0;
}
.user-info .user-name {
  font-size: 0.82rem; color: #fff; font-weight: 500;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px;
}
.user-info .user-role {
  font-size: 0.68rem; color: rgba(255,255,255,0.4);
  text-transform: capitalize;
}
.btn-logout {
  background: none; border: none; color: rgba(255,255,255,0.4);
  font-size: 1rem; cursor: pointer; padding: 4px;
  transition: color .15s;
}
.btn-logout:hover { color: var(--rose); }
/* ── Main content ────────────────────────────── */
.main-content {
  margin-left: var(--sidebar-w);
  min-height: 100vh;
  display: flex; flex-direction: column;
}
.topbar {
  background: #fff;
  border-bottom: 1px solid #ede4dd;
  padding: 14px 28px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
}
.topbar-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  color: #1a1218;
}
.topbar-right {
  display: flex; align-items: center; gap: 12px;
  font-size: 0.82rem; color: var(--muted);
}
.page-body { padding: 28px; flex: 1; }
/* ── Cards ───────────────────────────────────── */
.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 22px 24px;
  border: 1px solid #ede4dd;
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
.stat-card .stat-icon {
  width: 46px; height: 46px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; margin-bottom: 14px;
}
.stat-card .stat-value {
  font-family: 'Playfair Display', serif;
  font-size: 1.7rem; color: #1a1218; line-height: 1;
}
.stat-card .stat-label {
  font-size: 0.77rem; color: var(--muted);
  text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px;
}
.card-rose  { background: linear-gradient(135deg, var(--rose), #a0303a); }
.card-gold  { background: linear-gradient(135deg, var(--gold), #b8860b); }
.card-green { background: linear-gradient(135deg, #3cba8f, #1a7a5e); }
.card-blue  { background: linear-gradient(135deg, #5b9bd5, #2a6fa8); }
/* ── Tables ──────────────────────────────────── */
.ais-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
.ais-table thead th {
  background: #f5ede8; color: var(--muted);
  text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.07em;
  padding: 11px 14px; border-bottom: 2px solid #ede4dd;
  font-weight: 500;
}
.ais-table tbody td { padding: 11px 14px; border-bottom: 1px solid #f0e8e1; color: #1a1218; }
.ais-table tbody tr:hover { background: #fdf8f2; }
.ais-table tbody tr:last-child td { border-bottom: none; }
/* ── Badges ──────────────────────────────────── */
.badge-cash   { background: #dcfce7; color: #166534; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; }
.badge-ewallet{ background: #dbeafe; color: #1e40af; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; }
.badge-completed { background: #dcfce7; color: #166534; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; }
.badge-void      { background: #fee2e2; color: #991b1b; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; }
.badge-refunded  { background: #fef3c7; color: #92400e; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; }
/* ── Section cards ───────────────────────────── */
.section-card {
  background: #fff; border-radius: 14px;
  border: 1px solid #ede4dd; overflow: hidden;
}
.section-card-header {
  padding: 16px 22px; border-bottom: 1px solid #ede4dd;
  display: flex; align-items: center; justify-content: space-between;
}
.section-card-header .section-title {
  font-weight: 500; font-size: 0.92rem; color: #1a1218;
}
.section-card-body { padding: 20px 22px; }
/* ── Form styles ─────────────────────────────── */
.ais-input {
  border: 1.5px solid #e8ddd6; border-radius: 10px;
  padding: 10px 14px; font-size: 0.88rem; color: #1a1218;
  background: #fff; width: 100%; transition: border-color .2s;
}
.ais-input:focus {
  outline: none; border-color: var(--rose);
  box-shadow: 0 0 0 3px rgba(201,99,106,0.1);
}
.ais-label {
  font-size: 0.74rem; font-weight: 500; letter-spacing: 0.06em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 6px; display: block;
}
.btn-rose {
  background: linear-gradient(135deg, var(--rose), #a0303a);
  color: #fff; border: none; border-radius: 10px;
  padding: 10px 22px; font-size: 0.88rem; font-weight: 500;
  cursor: pointer; transition: opacity .2s, transform .1s;
}
.btn-rose:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
.btn-gold {
  background: linear-gradient(135deg, var(--gold), #b8860b);
  color: #fff; border: none; border-radius: 10px;
  padding: 10px 22px; font-size: 0.88rem; font-weight: 500;
  cursor: pointer; transition: opacity .2s;
}
.btn-gold:hover { opacity: .9; color: #fff; }
/* ── Flash messages ──────────────────────────── */
.flash-success {
  background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px;
  color: #166534; padding: 12px 16px; display: flex; align-items: center; gap: 8px;
  margin-bottom: 20px; font-size: 0.87rem;
}
.flash-error {
  background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px;
  color: #b91c1c; padding: 12px 16px; display: flex; align-items: center; gap: 8px;
  margin-bottom: 20px; font-size: 0.87rem;
}
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); transition: transform .3s; }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-left: 0; }
}
</style>
</head>
<body>

<!-- Mobile sidebar toggle button -->
<button class="sidebar-toggle-btn d-md-none" id="sidebar-toggle" aria-label="Toggle sidebar">
  <i class="bi bi-list"></i>
</button>
<!-- Mobile overlay background -->
<div class="overlay-bg" id="sidebar-overlay"></div>

<!-- ── Sidebar ─────────────────────────────────────── -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="brand-name">MJ <span>Salon</span></div>
    <div class="brand-sub">AIS Platform</div>
  </div>

  <?php if ($role === 'admin'): ?>
  <div class="sidebar-section">Utama</div>
  <a href="<?= base_url('admin/dashboard.php') ?>"
     class="nav-item-link <?= ($current_file === 'dashboard.php') ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>

  <div class="sidebar-section">Laporan</div>
  <a href="<?= base_url('admin/reports.php') ?>"
     class="nav-item-link <?= ($current_file === 'reports.php') ? 'active' : '' ?>">
    <i class="bi bi-bar-chart-line"></i> Laporan Keuangan
  </a>
  <a href="<?= base_url('admin/journals.php') ?>"
     class="nav-item-link <?= ($current_file === 'journals.php') ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> Jurnal Umum
  </a>

  <div class="sidebar-section">Transaksi</div>
  <a href="<?= base_url('admin/transactions.php') ?>"
     class="nav-item-link <?= ($current_file === 'transactions.php') ? 'active' : '' ?>">
    <i class="bi bi-receipt"></i> Pendapatan Jasa
  </a>
  <a href="<?= base_url('admin/purchasing.php') ?>"
     class="nav-item-link <?= in_array($current_file, ['purchasing.php','purchase_form.php','purchase_detail.php']) ? 'active' : '' ?>">
    <i class="bi bi-cart3"></i> Pembelian Barang
  </a>
  <a href="<?= base_url('admin/expenses.php') ?>"
     class="nav-item-link <?= in_array($current_file, ['expenses.php','expense_form.php','expense_detail.php']) ? 'active' : '' ?>">
    <i class="bi bi-cash-coin"></i> Pengeluaran
  </a>

  <div class="sidebar-section">Master Data</div>
  <a href="<?= base_url('admin/customers.php') ?>"
     class="nav-item-link <?= ($current_file === 'customers.php') ? 'active' : '' ?>">
    <i class="bi bi-people"></i> Pelanggan
  </a>
  <a href="<?= base_url('admin/services.php') ?>"
     class="nav-item-link <?= ($current_file === 'services.php') ? 'active' : '' ?>">
    <i class="bi bi-scissors"></i> Layanan
  </a>
  <a href="<?= base_url('admin/products.php') ?>"
     class="nav-item-link <?= ($current_file === 'products.php') ? 'active' : '' ?>">
    <i class="bi bi-box-seam"></i> Produk/Stok
  </a>
  <a href="<?= base_url('admin/suppliers.php') ?>"
     class="nav-item-link <?= ($current_file === 'suppliers.php') ? 'active' : '' ?>">
    <i class="bi bi-truck"></i> Supplier
  </a>
  <a href="<?= base_url('admin/user_management.php') ?>"
     class="nav-item-link <?= ($current_file === 'user_management.php') ? 'active' : '' ?>">
    <i class="bi bi-person-gear"></i> Kelola User
  </a>

  <div class="sidebar-section">Akun</div>
  <a href="<?= base_url('admin/change_password.php') ?>"
     class="nav-item-link <?= ($current_file === 'change_password.php') ? 'active' : '' ?>">
    <i class="bi bi-shield-lock"></i> Ganti Password
  </a>

  <?php else: ?>
  <div class="sidebar-section">Operasional</div>
  <a href="<?= base_url('staff/transaction.php') ?>"
     class="nav-item-link <?= ($current_file === 'transaction.php') ? 'active' : '' ?>">
    <i class="bi bi-plus-circle"></i> Transaksi Baru
  </a>
  <a href="<?= base_url('staff/history.php') ?>"
     class="nav-item-link <?= ($current_file === 'history.php') ? 'active' : '' ?>">
    <i class="bi bi-clock-history"></i> Riwayat Transaksi
  </a>

  <div class="sidebar-section">Akun</div>
  <a href="<?= base_url('staff/change_password.php') ?>"
     class="nav-item-link <?= ($current_file === 'change_password.php') ? 'active' : '' ?>">
    <i class="bi bi-shield-lock"></i> Ganti Password
  </a>
  <?php endif; ?>

  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
      <div class="user-role"><?= $role ?></div>
    </div>
    <a href="<?= base_url('logout.php') ?>" class="btn-logout ms-auto" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<!-- ── Main Content Wrapper ───────────────────────── -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><?= htmlspecialchars($page_title) ?></div>
    <div class="topbar-right">
      <i class="bi bi-calendar3"></i>
      <?= date('l, d F Y') ?>
      <span class="badge bg-warning text-dark ms-2" style="font-size:0.7rem;border-radius:20px;">
        <?= ucfirst($role)=="Admin" ? "Admin" : "Staf" ?>
      </span>
    </div>
  </div>

  <div class="page-body">

    <?php if ($flash['msg']): ?>
    <div class="flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
      <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>-fill"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>
