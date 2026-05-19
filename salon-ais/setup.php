<?php
// ============================================================
// MJ Salon AIS - Web Installer (Updated)
// setup.php — hapus file ini setelah install berhasil!
// ============================================================
$cfg = ['host'=>'localhost','user'=>'root','pass'=>'','db'=>'mj_salon_ais'];
$steps   = [];
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass']);
    if ($conn->connect_error) {
        $steps[] = ['label'=>'Koneksi ke MySQL','ok'=>false,'detail'=>$conn->connect_error];
        $success = false;
        goto render;
    }
    $conn->set_charset('utf8mb4');
    $steps[] = ['label'=>'Koneksi ke MySQL Server','ok'=>true,'detail'=>'Terhubung ke '.$cfg['host']];

    // Buat database
    if ($conn->query("CREATE DATABASE IF NOT EXISTS `{$cfg['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        $steps[] = ['label'=>'Buat database `'.$cfg['db'].'`','ok'=>true,'detail'=>''];
    } else {
        $steps[] = ['label'=>'Buat database','ok'=>false,'detail'=>$conn->error];
        $success=false; goto render;
    }
    $conn->select_db($cfg['db']);

    // Buat semua tabel
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role ENUM('admin','staff') NOT NULL DEFAULT 'staff', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'customers' => "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, email VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'services' => "CREATE TABLE IF NOT EXISTS services (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, category VARCHAR(50) NOT NULL, price DECIMAL(12,2) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'transactions' => "CREATE TABLE IF NOT EXISTS transactions (id INT AUTO_INCREMENT PRIMARY KEY, receipt_no VARCHAR(30) NOT NULL UNIQUE, customer_id INT NOT NULL, staff_id INT NOT NULL, transaction_date DATE NOT NULL, subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, discount DECIMAL(12,2) NOT NULL DEFAULT 0, total_amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_method ENUM('cash','ewallet') NOT NULL DEFAULT 'cash', amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0, change_amount DECIMAL(12,2) NOT NULL DEFAULT 0, status ENUM('completed','refunded','void') NOT NULL DEFAULT 'completed', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (customer_id) REFERENCES customers(id), FOREIGN KEY (staff_id) REFERENCES users(id)) ENGINE=InnoDB",
        'transaction_details' => "CREATE TABLE IF NOT EXISTS transaction_details (id INT AUTO_INCREMENT PRIMARY KEY, transaction_id INT NOT NULL, service_id INT NOT NULL, service_name VARCHAR(100) NOT NULL, qty INT NOT NULL DEFAULT 1, unit_price DECIMAL(12,2) NOT NULL, subtotal DECIMAL(12,2) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (transaction_id) REFERENCES transactions(id), FOREIGN KEY (service_id) REFERENCES services(id)) ENGINE=InnoDB",
        'journals' => "CREATE TABLE IF NOT EXISTS journals (id INT AUTO_INCREMENT PRIMARY KEY, ref_type ENUM('transaction','purchase','expense') NOT NULL DEFAULT 'transaction', ref_id INT NOT NULL, journal_date DATE NOT NULL, account_code VARCHAR(20) NOT NULL, account_name VARCHAR(100) NOT NULL, debit DECIMAL(12,2) NOT NULL DEFAULT 0, credit DECIMAL(12,2) NOT NULL DEFAULT 0, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'suppliers' => "CREATE TABLE IF NOT EXISTS suppliers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, phone VARCHAR(20), address TEXT, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'products' => "CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, category VARCHAR(50) NOT NULL, unit VARCHAR(20) NOT NULL DEFAULT 'pcs', stock INT NOT NULL DEFAULT 0, min_stock INT NOT NULL DEFAULT 5, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        'purchases' => "CREATE TABLE IF NOT EXISTS purchases (id INT AUTO_INCREMENT PRIMARY KEY, purchase_no VARCHAR(30) NOT NULL UNIQUE, supplier_id INT, staff_id INT NOT NULL, purchase_date DATE NOT NULL, total_amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_method ENUM('cash','transfer','kredit') NOT NULL DEFAULT 'cash', status ENUM('completed','cancelled') NOT NULL DEFAULT 'completed', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL, FOREIGN KEY (staff_id) REFERENCES users(id)) ENGINE=InnoDB",
        'purchase_items' => "CREATE TABLE IF NOT EXISTS purchase_items (id INT AUTO_INCREMENT PRIMARY KEY, purchase_id INT NOT NULL, product_id INT, item_name VARCHAR(100) NOT NULL, qty INT NOT NULL DEFAULT 1, unit VARCHAR(20) NOT NULL DEFAULT 'pcs', unit_price DECIMAL(12,2) NOT NULL, subtotal DECIMAL(12,2) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (purchase_id) REFERENCES purchases(id), FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL) ENGINE=InnoDB",
        'expenses' => "CREATE TABLE IF NOT EXISTS expenses (id INT AUTO_INCREMENT PRIMARY KEY, expense_no VARCHAR(30) NOT NULL UNIQUE, staff_id INT NOT NULL, expense_date DATE NOT NULL, category VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, total_amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_method ENUM('cash','transfer') NOT NULL DEFAULT 'cash', status ENUM('completed','cancelled') NOT NULL DEFAULT 'completed', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (staff_id) REFERENCES users(id)) ENGINE=InnoDB",
    ];
    foreach ($tables as $tname => $tsql) {
        if ($conn->query($tsql)) {
            $steps[] = ['label'=>"Tabel `$tname`",'ok'=>true,'detail'=>''];
        } else {
            $steps[] = ['label'=>"Tabel `$tname`",'ok'=>false,'detail'=>$conn->error];
            $success=false;
        }
    }

    // Seed users
    $demo = [['MJ Admin','admin','password','admin'],['Sarah Putri','sarah','password','staff'],['Maria Dewi','maria','password','staff'],['Rina Susanti','rina','password','staff']];
    $added = 0;
    foreach ($demo as [$n,$u,$p,$r]) {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $chk->bind_param('s',$u); $chk->execute(); $chk->store_result();
        if ($chk->num_rows===0) { $h=password_hash($p,PASSWORD_BCRYPT); $conn->query("INSERT INTO users (name,username,password,role) VALUES ('$n','$u','$h','$r')"); $added++; }
        $chk->close();
    }
    $steps[] = ['label'=>'Seed akun demo','ok'=>true,'detail'=>"$added akun baru dibuat. Semua password: 'password'"];

    // Seed dari database.sql jika tersedia
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $steps[] = ['label'=>'File database.sql ditemukan','ok'=>true,'detail'=>'Import manual via phpMyAdmin juga tersedia untuk data sample lengkap (3 bulan)'];
    }

    // Check counts
    $sc = (int)$conn->query("SELECT COUNT(*) AS c FROM services")->fetch_assoc()['c'];
    if ($sc === 0) {
        // Seed services
        $svcs = [['Haircut','Hair',50000],['Haircut + Wash & Dry','Hair',70000],['Blow Dry','Hair',50000],['Hair Curly Styling','Hair',75000],['Blow Straight','Hair',60000],['Blow Curly','Hair',100000],['Hair Mask (Short)','Hair',80000],['Hair Mask (Long)','Hair',90000],['Hair Mask LOreal','Hair',100000],['Hair Mask Keratin','Hair',120000],['Creambath (Short)','Hair',70000],['Creambath (Long)','Hair',80000],['Creambath LOreal','Hair',100000],['Creambath Keratin','Hair',120000],['Hair Toning (Short)','Hair',100000],['Hair Toning (Long)','Hair',200000],['Hair Coloring','Hair',350000],['Hair Highlights','Hair',300000],['Smoothing / Rebonding','Hair',450000],['Smoothing + Coloring','Hair',650000],['Hair Perm','Hair',225000],['Basic Facial','Facial',70000],['Premium Facial','Facial',85000],['Eyebrow Threading','Waxing',20000],['Makeup + Hair Do','Makeup',300000],['Manicure','Nails',60000],['Pedicure','Nails',80000],['Mani-Pedi Combo','Nails',130000]];
        foreach ($svcs as [$n,$c,$p]) {
            $conn->query("INSERT INTO services (name,category,price) VALUES ('".addslashes($n)."','$c',$p)");
        }
        $steps[] = ['label'=>'Seed layanan salon','ok'=>true,'detail'=>count($svcs).' layanan ditambahkan'];
    } else {
        $steps[] = ['label'=>'Seed layanan salon','ok'=>true,'detail'=>"Dilewati — $sc layanan sudah ada"];
    }

    // Seed suppliers if empty
    $suplc = (int)$conn->query("SELECT COUNT(*) AS c FROM suppliers")->fetch_assoc()['c'];
    if ($suplc === 0) {
        $conn->query("INSERT INTO suppliers (name,phone,address) VALUES ('PT Loreal Indonesia','021-5551234','Jakarta'),('Toko Kosmetik Cantik','0812-3456789','Tanah Abang'),('CV Salon Supply','0813-4567890','Jakarta Barat')");
        $steps[] = ['label'=>'Seed supplier','ok'=>true,'detail'=>'3 supplier ditambahkan'];
    } else {
        $steps[] = ['label'=>'Seed supplier','ok'=>true,'detail'=>"Dilewati — $suplc supplier sudah ada"];
    }

    // Verify
    $vc = (int)$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
    $steps[] = ['label'=>'Verifikasi instalasi','ok'=>$vc>0,'detail'=>"$vc user ditemukan di database"];
    $conn->close();
}

render:
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Installer — MJ Salon AIS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--rose:#c9636a;--gold:#d4a84b}
body{background:#f5ede8;font-family:'Helvetica Neue',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px}
.card{background:#fff;border-radius:18px;width:100%;max-width:580px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.12)}
.card-head{background:linear-gradient(135deg,#1a1218,#3d2a34);padding:28px;text-align:center;color:#fff}
.card-head h1{font-size:1.5rem;font-weight:700;margin:0}.card-head span{color:var(--gold)}
.card-head p{color:rgba(255,255,255,0.45);font-size:0.78rem;margin:6px 0 0}
.card-body{padding:28px}
.step{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f5ede8}
.step:last-child{border-bottom:none}
.step-icon{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.78rem;flex-shrink:0}
.ok{background:#dcfce7;color:#166534}.fail{background:#fee2e2;color:#b91c1c}
.step-label{font-size:0.85rem;font-weight:500;color:#1a1218}
.step-detail{font-size:0.72rem;color:#888;margin-top:2px}
.btn-install{background:linear-gradient(135deg,var(--rose),#a0303a);color:#fff;border:none;border-radius:10px;padding:13px;font-size:0.95rem;font-weight:500;width:100%;cursor:pointer}
.btn-go{background:linear-gradient(135deg,#166534,#14532d);color:#fff;border:none;border-radius:10px;padding:12px;font-size:0.9rem;width:100%;cursor:pointer;text-decoration:none;display:block;text-align:center;margin-top:8px}
.warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:12px;font-size:0.78rem;color:#92400e;margin-top:14px}
.accounts{background:#f5ede8;border-radius:10px;padding:14px;font-size:0.8rem;margin-bottom:18px}
</style>
</head>
<body>
<div class="card">
  <div class="card-head">
    <h1>MJ <span>Salon</span> AIS</h1>
    <p>Installer & Setup Database</p>
  </div>
  <div class="card-body">
    <?php if (empty($steps)): ?>
    <p style="font-size:0.88rem;color:#555;margin-bottom:16px;">Installer akan membuat database <strong>mj_salon_ais</strong> beserta semua tabel dan data awal di MySQL lokal (Laragon/XAMPP).</p>
    <div class="accounts">
      <strong>Akun Demo yang akan dibuat:</strong>
      <div class="mt-2 d-flex flex-wrap gap-2">
        <span style="background:#fce7ea;color:var(--rose);padding:3px 10px;border-radius:20px;font-size:0.75rem;">admin / password (Admin)</span>
        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;">sarah / password (Staff)</span>
        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;">maria / password (Staff)</span>
        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;">rina / password (Staff)</span>
      </div>
    </div>
    <form method="POST">
      <button type="submit" class="btn-install"><i class="bi bi-play-fill me-2"></i>Jalankan Installer</button>
    </form>
    <div style="margin-top:14px;font-size:0.77rem;color:#aaa;text-align:center;">
      <i class="bi bi-info-circle me-1"></i>
      Untuk data sample 3 bulan lengkap, import <code>database.sql</code> via phpMyAdmin setelah install.
    </div>
    <?php else: ?>
    <h6 style="font-weight:600;margin-bottom:14px;">Hasil Instalasi</h6>
    <div>
      <?php foreach ($steps as $s): ?>
      <div class="step">
        <div class="step-icon <?= $s['ok']?'ok':'fail' ?>">
          <i class="bi bi-<?= $s['ok']?'check':'x' ?>-lg"></i>
        </div>
        <div>
          <div class="step-label"><?= htmlspecialchars($s['label']) ?></div>
          <?php if ($s['detail']): ?>
          <div class="step-detail"><?= htmlspecialchars($s['detail']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($success): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:14px;margin-top:16px;text-align:center;">
      <i class="bi bi-check-circle-fill" style="color:#166534;font-size:1.6rem;"></i>
      <div style="font-weight:600;color:#166534;margin-top:6px;">Instalasi Berhasil!</div>
      <div style="font-size:0.78rem;color:#555;margin-top:3px;">MJ Salon AIS siap digunakan.</div>
    </div>
    <a href="index.php" class="btn-go"><i class="bi bi-arrow-right-circle me-2"></i>Buka MJ Salon AIS</a>
    <div class="warn">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <strong>Penting:</strong> Hapus file <code>setup.php</code> dari server setelah instalasi untuk keamanan!<br>
      <span class="mt-1 d-block">Untuk data 3 bulan, import <code>database.sql</code> via <strong>phpMyAdmin → Import</strong>.</span>
    </div>
    <?php else: ?>
    <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:14px;margin-top:16px;text-align:center;">
      <i class="bi bi-x-circle-fill" style="color:#b91c1c;font-size:1.6rem;"></i>
      <div style="font-weight:600;color:#b91c1c;margin-top:6px;">Instalasi Gagal</div>
      <div style="font-size:0.78rem;color:#555;margin-top:3px;">Pastikan MySQL aktif dan setting koneksi benar.</div>
    </div>
    <form method="POST" style="margin-top:12px;">
      <button type="submit" class="btn-install"><i class="bi bi-arrow-clockwise me-2"></i>Coba Lagi</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
