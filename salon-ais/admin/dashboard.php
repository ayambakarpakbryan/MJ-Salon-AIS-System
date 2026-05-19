<?php
// ============================================================
// MJ Salon AIS - Dashboard Admin
// admin/dashboard.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();
$page_title = 'Dashboard';
$conn = db_connect();
$today       = date('Y-m-d');
$month_start = date('Y-m-01');
$days30_ago  = date('Y-m-d', strtotime('-30 days'));

// ── KPIs ──────────────────────────────────────────────────────
$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM transactions WHERE transaction_date='$today' AND status='completed'");
$today_revenue = (float)$r->fetch_assoc()['v'];

$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM transactions WHERE transaction_date BETWEEN '$month_start' AND '$today' AND status='completed'");
$month_revenue = (float)$r->fetch_assoc()['v'];

$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM expenses WHERE expense_date BETWEEN '$month_start' AND '$today' AND status='completed'");
$month_expense = (float)$r->fetch_assoc()['v'];

$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM purchases WHERE purchase_date BETWEEN '$month_start' AND '$today' AND status='completed'");
$month_purchase = (float)$r->fetch_assoc()['v'];

$r = $conn->query("SELECT COUNT(*) AS v FROM transactions WHERE transaction_date='$today' AND status='completed'");
$today_txn = (int)$r->fetch_assoc()['v'];

$r = $conn->query("SELECT COUNT(*) AS v FROM customers");
$total_customers = (int)$r->fetch_assoc()['v'];

$month_net = $month_revenue - $month_expense - $month_purchase;

// ── Deteksi apakah data sample belum diupdate ─────────────────
$r = $conn->query("SELECT COUNT(*) AS v FROM transactions WHERE status='completed'");
$total_tx_all = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) AS v FROM transactions WHERE transaction_date BETWEEN '$days30_ago' AND '$today' AND status='completed'");
$tx_30days = (int)$r->fetch_assoc()['v'];
$data_stale = ($total_tx_all > 5 && $tx_30days === 0); // Ada data tapi tidak ada yang recent

// ── Low stock ─────────────────────────────────────────────────
$low_stock = $conn->query("SELECT name, stock, min_stock, unit FROM products WHERE stock <= min_stock AND is_active=1 ORDER BY stock ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// ── Chart: last 7 days ────────────────────────────────────────
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d   = date('Y-m-d', strtotime("-$i days"));
    $rev = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM transactions WHERE transaction_date='$d' AND status='completed'")->fetch_assoc()['v'];
    $exp = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM expenses WHERE expense_date='$d' AND status='completed'")->fetch_assoc()['v'];
    $pur = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) AS v FROM purchases WHERE purchase_date='$d' AND status='completed'")->fetch_assoc()['v'];
    $chart_data[] = ['date' => date('d M', strtotime($d)), 'rev' => $rev, 'exp' => $exp + $pur];
}

// Recent transactions
$recent = $conn->query("
    SELECT t.receipt_no, t.transaction_date, t.total_amount, t.payment_method, t.status,
           c.name AS customer, u.name AS staff
    FROM transactions t JOIN customers c ON c.id=t.customer_id JOIN users u ON u.id=t.staff_id
    ORDER BY t.created_at DESC LIMIT 8
");

// Top services this month
$top_svcs = $conn->query("
    SELECT td.service_name, SUM(td.qty) AS qty, SUM(td.subtotal) AS rev
    FROM transaction_details td JOIN transactions t ON t.id=td.transaction_id
    WHERE t.transaction_date BETWEEN '$month_start' AND '$today' AND t.status='completed'
    GROUP BY td.service_name ORDER BY rev DESC LIMIT 5
");

// Recent expenses
$recent_exp = $conn->query("
    SELECT category, description, total_amount, expense_date
    FROM expenses WHERE status='completed'
    ORDER BY expense_date DESC, created_at DESC LIMIT 5
");

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- KPI Row 1 -->
<?php if ($data_stale): ?>
<div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:12px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
  <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:1.4rem;flex-shrink:0;"></i>
  <div class="flex-grow-1">
    <div style="font-weight:600;color:#92400e;font-size:.9rem;">Data sample perlu diupdate tanggalnya</div>
    <div style="font-size:.8rem;color:#78350f;margin-top:2px;">
      Ada <?= $total_tx_all ?> transaksi di database tapi tanggalnya masih 2025. Dashboard tidak bisa membacanya sebagai data "hari ini / bulan ini".
    </div>
  </div>
  <a href="<?= base_url('fix_dates.php') ?>"
     style="background:#d97706;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:.82rem;font-weight:500;white-space:nowrap;flex-shrink:0;">
    🔧 Fix Sekarang (1 Klik)
  </a>
</div>
<?php endif; ?>

<!-- KPI Row 1 -->
<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fce7ea;color:var(--rose);"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-value">Rp <?= number_format($today_revenue,0,',','.') ?></div>
      <div class="stat-label">Pendapatan Hari Ini</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3d8;color:var(--gold);"><i class="bi bi-graph-up"></i></div>
      <div class="stat-value" style="font-size:1.4rem;">Rp <?= number_format($month_revenue,0,',','.') ?></div>
      <div class="stat-label">Pendapatan Bulan Ini</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-cash-coin"></i></div>
      <div class="stat-value" style="font-size:1.4rem;">Rp <?= number_format($month_expense + $month_purchase,0,',','.') ?></div>
      <div class="stat-label">Total Pengeluaran Bulan Ini</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= $month_net >= 0 ? '#dcfce7;color:#166534' : '#fee2e2;color:#b91c1c' ?>;"><i class="bi bi-bar-chart-line"></i></div>
      <div class="stat-value" style="font-size:1.4rem;color:<?= $month_net >= 0 ? '#166534' : '#b91c1c' ?>;">
        Rp <?= number_format(abs($month_net),0,',','.') ?>
      </div>
      <div class="stat-label">Laba Bersih Bulan Ini <?= $month_net < 0 ? '(Rugi)' : '' ?></div>
    </div>
  </div>
</div>

<!-- KPI Row 2 -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dcfce7;color:#166534;"><i class="bi bi-receipt-cutoff"></i></div>
      <div class="stat-value"><?= $today_txn ?></div>
      <div class="stat-label">Transaksi Hari Ini</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:#1e40af;"><i class="bi bi-people"></i></div>
      <div class="stat-value"><?= $total_customers ?></div>
      <div class="stat-label">Total Pelanggan</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3d8;color:var(--gold);"><i class="bi bi-cart3"></i></div>
      <div class="stat-value" style="font-size:1.4rem;">Rp <?= number_format($month_purchase,0,',','.') ?></div>
      <div class="stat-label">Pembelian Bulan Ini</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= empty($low_stock)?'#dcfce7;color:#166534':'#fef3c7;color:#92400e' ?>;"><i class="bi bi-box-seam"></i></div>
      <div class="stat-value"><?= count($low_stock) ?></div>
      <div class="stat-label">Produk Stok Rendah</div>
    </div>
  </div>
</div>

<!-- Chart + Low Stock -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-bar-chart me-2" style="color:var(--rose)"></i>Pendapatan vs Pengeluaran (7 Hari)</span>
      </div>
      <div class="section-card-body">
        <canvas id="revenueChart" height="90"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-exclamation-triangle me-2" style="color:var(--gold)"></i>Stok Rendah</span>
      </div>
      <div class="section-card-body">
        <?php if (empty($low_stock)): ?>
        <div style="text-align:center;padding:20px;color:var(--muted);font-size:0.83rem;">
          <i class="bi bi-check-circle" style="font-size:1.5rem;color:#166534;display:block;margin-bottom:8px;"></i>
          Semua stok aman
        </div>
        <?php endif; ?>
        <?php foreach ($low_stock as $p): ?>
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom:1px solid #f0e8e1;">
          <div>
            <div style="font-size:0.83rem;font-weight:500;"><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:0.72rem;color:var(--muted);">Min: <?= $p['min_stock'] ?> <?= $p['unit'] ?></div>
          </div>
          <span style="background:#fee2e2;color:#b91c1c;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">
            <?= $p['stock'] ?> <?= $p['unit'] ?>
          </span>
        </div>
        <?php endforeach; ?>
        <a href="products.php" style="font-size:0.78rem;color:var(--rose);text-decoration:none;">
          Kelola Produk → 
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Transactions + Top Services + Recent Expenses -->
<div class="row g-3">
  <div class="col-lg-5">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-clock-history me-2" style="color:var(--rose)"></i>Transaksi Terbaru</span>
        <a href="transactions.php" style="font-size:0.78rem;color:var(--rose);text-decoration:none;">Lihat Semua →</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead><tr><th>Receipt</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
              <td><code style="font-size:0.72rem;"><?= htmlspecialchars(substr($row['receipt_no'],4)) ?></code></td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($row['customer']) ?></td>
              <td style="font-weight:500;font-size:0.82rem;">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
              <td><span class="badge-<?= $row['status'] ?>" style="font-size:0.68rem;"><?= ucfirst($row['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-3">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-trophy me-2" style="color:var(--gold)"></i>Top Layanan</span>
      </div>
      <div class="section-card-body">
        <?php $rank=1; while ($s = $top_svcs->fetch_assoc()): ?>
        <div class="d-flex align-items-start gap-2 mb-2">
          <div style="width:22px;height:22px;border-radius:50%;background:<?= $rank===1?'var(--gold)':'var(--rose)' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;flex-shrink:0;margin-top:2px;"><?= $rank ?></div>
          <div class="flex-grow-1">
            <div style="font-size:0.79rem;font-weight:500;line-height:1.2;"><?= htmlspecialchars($s['service_name']) ?></div>
            <div style="font-size:0.7rem;color:var(--muted);"><?= $s['qty'] ?>x · Rp <?= number_format($s['rev'],0,',','.') ?></div>
          </div>
        </div>
        <?php $rank++; endwhile; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-cash-coin me-2" style="color:var(--rose)"></i>Pengeluaran Terbaru</span>
        <a href="expenses.php" style="font-size:0.78rem;color:var(--rose);text-decoration:none;">Lihat Semua →</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Jumlah</th></tr></thead>
          <tbody>
            <?php while ($e = $recent_exp->fetch_assoc()): ?>
            <tr>
              <td style="font-size:0.75rem;color:var(--muted);"><?= date('d/m', strtotime($e['expense_date'])) ?></td>
              <td style="font-size:0.79rem;"><?= htmlspecialchars(substr($e['description'],0,30)) ?><?= strlen($e['description'])>30?'...':'' ?></td>
              <td style="font-weight:500;font-size:0.79rem;color:#b91c1c;">Rp <?= number_format($e['total_amount'],0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$chart_labels = json_encode(array_column($chart_data, 'date'));
$chart_rev    = json_encode(array_column($chart_data, 'rev'));
$chart_exp    = json_encode(array_column($chart_data, 'exp'));
$extra_js = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revenueChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: $chart_labels,
    datasets: [
      { label: 'Pendapatan', data: $chart_rev, backgroundColor: 'rgba(201,99,106,0.75)', borderColor: '#c9636a', borderWidth: 1.5, borderRadius: 5 },
      { label: 'Pengeluaran', data: $chart_exp, backgroundColor: 'rgba(212,168,75,0.6)', borderColor: '#d4a84b', borderWidth: 1.5, borderRadius: 5 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
  }
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
