<?php
// ============================================================
// MJ Salon AIS - Laporan Keuangan (Updated - Laba Rugi)
// admin/reports.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Laporan Keuangan';
$conn = db_connect();

$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to   = $_GET['to']   ?? date('Y-m-d');

$errors = [];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $errors[] = 'Tanggal awal tidak valid.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $errors[] = 'Tanggal akhir tidak valid.';
if (empty($errors) && $date_from > $date_to)            $errors[] = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';
if (empty($errors) && $date_to > date('Y-m-d'))         $errors[] = 'Tanggal akhir tidak boleh melebihi hari ini.';

// Defaults
$total_revenue = $total_txn = $cash_rev = $ewallet_rev = 0;
$total_expense = $total_purchase = 0;
$transactions = $expense_by_cat = $top_services = [];

if (empty($errors)) {
    // === PENDAPATAN ===
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt,
               COALESCE(SUM(total_amount),0) AS total_rev,
               COALESCE(SUM(CASE WHEN payment_method='cash'    THEN total_amount ELSE 0 END),0) AS cash_rev,
               COALESCE(SUM(CASE WHEN payment_method='ewallet' THEN total_amount ELSE 0 END),0) AS ew_rev
        FROM transactions
        WHERE transaction_date BETWEEN ? AND ? AND status='completed'
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $sum = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $total_revenue = (float)$sum['total_rev'];
    $total_txn     = (int)$sum['cnt'];
    $cash_rev      = (float)$sum['cash_rev'];
    $ewallet_rev   = (float)$sum['ew_rev'];

    // === PENGELUARAN OPERASIONAL ===
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount),0) AS v
        FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='completed'
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $total_expense = (float)$stmt->get_result()->fetch_assoc()['v'];
    $stmt->close();

    // Pengeluaran per kategori
    $stmt = $conn->prepare("
        SELECT category, COALESCE(SUM(total_amount),0) AS total
        FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='completed'
        GROUP BY category ORDER BY total DESC
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $expense_by_cat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // === PEMBELIAN BARANG ===
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount),0) AS v
        FROM purchases WHERE purchase_date BETWEEN ? AND ? AND status='completed'
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $total_purchase = (float)$stmt->get_result()->fetch_assoc()['v'];
    $stmt->close();

    // === TOP SERVICES ===
    $stmt = $conn->prepare("
        SELECT td.service_name, SUM(td.qty) AS qty, SUM(td.subtotal) AS rev
        FROM transaction_details td
        JOIN transactions t ON t.id=td.transaction_id
        WHERE t.transaction_date BETWEEN ? AND ? AND t.status='completed'
        GROUP BY td.service_name ORDER BY rev DESC LIMIT 8
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $top_services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // === TRANSACTION LIST ===
    $stmt = $conn->prepare("
        SELECT t.receipt_no, t.transaction_date, t.total_amount, t.payment_method, t.status,
               c.name AS customer, u.name AS staff
        FROM transactions t
        JOIN customers c ON c.id=t.customer_id
        JOIN users u     ON u.id=t.staff_id
        WHERE t.transaction_date BETWEEN ? AND ?
        ORDER BY t.transaction_date DESC, t.created_at DESC
    ");
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

// Kalkulasi Laba/Rugi
$total_beban = $total_expense + $total_purchase;
$laba_bersih = $total_revenue - $total_beban;
$margin      = $total_revenue > 0 ? ($laba_bersih / $total_revenue) * 100 : 0;

require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Date Filter -->
<div class="section-card mb-4">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-funnel me-2" style="color:var(--rose)"></i>Periode Laporan</span>
    <?php if (empty($errors) && !empty($transactions)): ?>
    <a href="export_pdf.php?from=<?= urlencode($date_from) ?>&to=<?= urlencode($date_to) ?>"
       class="btn-gold" style="font-size:0.78rem;padding:7px 16px;text-decoration:none;" target="_blank">
      <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </a>
    <?php endif; ?>
  </div>
  <div class="section-card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-sm-4">
        <label class="ais-label">Dari Tanggal</label>
        <input type="date" name="from" class="ais-input" value="<?= htmlspecialchars($date_from) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-sm-4">
        <label class="ais-label">Sampai Tanggal</label>
        <input type="date" name="to" class="ais-input" value="<?= htmlspecialchars($date_to) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-sm-4">
        <button type="submit" class="btn-rose w-100" style="padding:10px;">
          <i class="bi bi-search me-2"></i>Buat Laporan
        </button>
      </div>
    </form>
    <?php foreach ($errors as $err): ?>
    <div class="flash-error mt-3"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($errors)): ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fce7ea;color:var(--rose);"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-value" style="font-size:1.3rem;">Rp <?= number_format($total_revenue,0,',','.') ?></div>
      <div class="stat-label">Total Pendapatan</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-cash-coin"></i></div>
      <div class="stat-value" style="font-size:1.3rem;">Rp <?= number_format($total_beban,0,',','.') ?></div>
      <div class="stat-label">Total Beban/Biaya</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= $laba_bersih>=0?'#dcfce7;color:#166534':'#fee2e2;color:#b91c1c' ?>;"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="stat-value" style="font-size:1.3rem;color:<?= $laba_bersih>=0?'#166534':'#b91c1c' ?>;">
        Rp <?= number_format(abs($laba_bersih),0,',','.') ?>
      </div>
      <div class="stat-label">Laba Bersih <?= $laba_bersih<0?'(Rugi)':'' ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:#1e40af;"><i class="bi bi-percent"></i></div>
      <div class="stat-value" style="font-size:1.3rem;color:<?= $margin>=0?'#166534':'#b91c1c' ?>;">
        <?= number_format(abs($margin),1) ?>%
      </div>
      <div class="stat-label">Margin Keuntungan <?= $margin<0?'(Rugi)':'' ?></div>
    </div>
  </div>
</div>

<!-- Laporan Laba Rugi + Breakdown -->
<div class="row g-3 mb-4">
  <!-- Laba Rugi -->
  <div class="col-lg-5">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-file-text me-2" style="color:var(--rose)"></i>Laporan Laba/Rugi</span>
      </div>
      <div class="section-card-body">
        <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px;">
          <?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?>
        </div>

        <!-- Pendapatan -->
        <div style="font-size:0.8rem;font-weight:600;color:#166534;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Pendapatan</div>
        <div class="d-flex justify-content-between mb-1" style="font-size:0.83rem;padding:5px 0;border-bottom:1px solid #f0e8e1;">
          <span style="color:var(--muted);">Pendapatan Jasa (Cash)</span>
          <span>Rp <?= number_format($cash_rev,0,',','.') ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2" style="font-size:0.83rem;padding:5px 0;border-bottom:1px solid #f0e8e1;">
          <span style="color:var(--muted);">Pendapatan Jasa (E-Wallet)</span>
          <span>Rp <?= number_format($ewallet_rev,0,',','.') ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:0.9rem;font-weight:700;padding:8px;background:#f0fdf4;border-radius:8px;">
          <span>Total Pendapatan</span>
          <span style="color:#166534;">Rp <?= number_format($total_revenue,0,',','.') ?></span>
        </div>

        <!-- Beban -->
        <div style="font-size:0.8rem;font-weight:600;color:#b91c1c;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Beban / Biaya</div>
        <?php foreach ($expense_by_cat as $ec): ?>
        <div class="d-flex justify-content-between mb-1" style="font-size:0.83rem;padding:5px 0;border-bottom:1px solid #f0e8e1;">
          <span style="color:var(--muted);">Biaya <?= htmlspecialchars($ec['category']) ?></span>
          <span>Rp <?= number_format($ec['total'],0,',','.') ?></span>
        </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between mb-1" style="font-size:0.83rem;padding:5px 0;border-bottom:1px solid #f0e8e1;">
          <span style="color:var(--muted);">Pembelian Barang/Perlengkapan</span>
          <span>Rp <?= number_format($total_purchase,0,',','.') ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:0.9rem;font-weight:700;padding:8px;background:#fef2f2;border-radius:8px;">
          <span>Total Beban</span>
          <span style="color:#b91c1c;">Rp <?= number_format($total_beban,0,',','.') ?></span>
        </div>

        <!-- Laba Bersih -->
        <div class="d-flex justify-content-between" style="font-size:1rem;font-weight:700;padding:12px;background:<?= $laba_bersih>=0?'#f0fdf4':'#fef2f2' ?>;border-radius:10px;border:2px solid <?= $laba_bersih>=0?'#86efac':'#fca5a5' ?>;">
          <span><?= $laba_bersih>=0?'LABA BERSIH':'RUGI BERSIH' ?></span>
          <span style="color:<?= $laba_bersih>=0?'#166534':'#b91c1c' ?>;">
            Rp <?= number_format(abs($laba_bersih),0,',','.') ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Services -->
  <div class="col-lg-3">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-trophy me-2" style="color:var(--gold)"></i>Top Layanan</span>
      </div>
      <div class="section-card-body">
        <?php foreach ($top_services as $i => $svc): ?>
        <div class="d-flex align-items-start gap-2 mb-3">
          <div style="width:22px;height:22px;border-radius:50%;background:<?= $i===0?'var(--gold)':($i===1?'#aaa':'#cd7f32') ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;flex-shrink:0;margin-top:2px;"><?= $i+1 ?></div>
          <div class="flex-grow-1">
            <div style="font-size:0.8rem;font-weight:500;"><?= htmlspecialchars($svc['service_name']) ?></div>
            <div style="font-size:0.7rem;color:var(--muted);"><?= $svc['qty'] ?>x · Rp <?= number_format($svc['rev'],0,',','.') ?></div>
            <?php $pct = $top_services[0]['rev']>0 ? ($svc['rev']/$top_services[0]['rev'])*100 : 0; ?>
            <div style="background:#f0e8e1;border-radius:20px;height:4px;margin-top:4px;overflow:hidden;">
              <div style="width:<?= round($pct) ?>%;height:100%;background:var(--rose);border-radius:20px;"></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($top_services)): ?>
        <p style="color:var(--muted);font-size:0.82rem;">Tidak ada data layanan.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Ringkasan Angka -->
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-calculator me-2" style="color:var(--rose)"></i>Statistik</span>
      </div>
      <div class="section-card-body">
        <?php
        $avg_txn    = $total_txn > 0 ? $total_revenue / $total_txn : 0;
        $days       = max(1, (strtotime($date_to) - strtotime($date_from)) / 86400 + 1);
        $daily_avg  = $total_revenue / $days;
        $daily_exp  = $total_beban / $days;
        ?>
        <?php foreach ([
            ['Jumlah Transaksi Jasa',        $total_txn.' transaksi', false],
            ['Rata-rata Nilai Transaksi',     'Rp '.number_format($avg_txn,0,',','.'), false],
            ['Rata-rata Pendapatan/Hari',     'Rp '.number_format($daily_avg,0,',','.'), false],
            ['Rata-rata Pengeluaran/Hari',    'Rp '.number_format($daily_exp,0,',','.'), false],
            ['Jumlah Hari Periode',           $days.' hari', false],
            ['Pembayaran Cash',               'Rp '.number_format($cash_rev,0,',','.'), false],
            ['Pembayaran E-Wallet',           'Rp '.number_format($ewallet_rev,0,',','.'), false],
            ['Total Pembelian Barang',        'Rp '.number_format($total_purchase,0,',','.'), false],
        ] as [$label, $val, $highlight]): ?>
        <div class="d-flex justify-content-between mb-2 pb-2" style="border-bottom:1px solid #f5ede8;font-size:0.82rem;">
          <span style="color:var(--muted);"><?= $label ?></span>
          <span style="font-weight:500;"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Transaction Register -->
<div class="section-card">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-table me-2" style="color:var(--rose)"></i>Daftar Transaksi Jasa (<?= count($transactions) ?> data)</span>
  </div>
  <div style="overflow-x:auto;max-height:400px;overflow-y:auto;">
    <table class="ais-table">
      <thead>
        <tr><th>Receipt</th><th>Tanggal</th><th>Pelanggan</th><th>Staf</th><th>Metode</th><th>Jumlah</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">Tidak ada transaksi untuk periode ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($transactions as $row): ?>
        <tr>
          <td><code style="font-size:0.75rem;"><?= htmlspecialchars($row['receipt_no']) ?></code></td>
          <td style="font-size:0.82rem;"><?= date('d/m/Y', strtotime($row['transaction_date'])) ?></td>
          <td><?= htmlspecialchars($row['customer']) ?></td>
          <td style="font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($row['staff']) ?></td>
          <td><span class="badge-<?= $row['payment_method'] ?>"><?= $row['payment_method']==='cash'?'Cash':'E-Wallet' ?></span></td>
          <td style="font-weight:500;">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
          <td><span class="badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if (!empty($transactions)): ?>
      <tfoot>
        <tr style="background:#fdf8f2;">
          <td colspan="5" style="font-weight:700;padding:11px 14px;">TOTAL PENDAPATAN</td>
          <td style="font-weight:700;color:var(--rose);padding:11px 14px;">Rp <?= number_format($total_revenue,0,',','.') ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
