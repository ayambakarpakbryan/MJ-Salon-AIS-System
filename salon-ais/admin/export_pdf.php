<?php
// ============================================================
// MJ Salon AIS - Export Laporan Keuangan (Print to PDF)
// admin/export_pdf.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$date_from = trim($_GET['from'] ?? date('Y-m-01'));
$date_to   = trim($_GET['to']   ?? date('Y-m-d'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) || $date_from > $date_to) {
    die('<p style="font-family:sans-serif;padding:30px;color:#c0392b;">Periode tidak valid. <a href="reports.php">← Kembali</a></p>');
}

$conn = db_connect();

// Pendapatan
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev, COALESCE(SUM(CASE WHEN payment_method='cash' THEN total_amount ELSE 0 END),0) AS cash_r, COALESCE(SUM(CASE WHEN payment_method='ewallet' THEN total_amount ELSE 0 END),0) AS ew_r FROM transactions WHERE transaction_date BETWEEN ? AND ? AND status='completed'");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$rev_summary = $stmt->get_result()->fetch_assoc(); $stmt->close();

// Pengeluaran per kategori
$stmt = $conn->prepare("SELECT category, COALESCE(SUM(total_amount),0) AS total FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='completed' GROUP BY category ORDER BY total DESC");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$exp_cats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
$total_expense = array_sum(array_column($exp_cats, 'total'));

// Pembelian
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS v FROM purchases WHERE purchase_date BETWEEN ? AND ? AND status='completed'");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$total_purchase = (float)$stmt->get_result()->fetch_assoc()['v']; $stmt->close();

// Transaksi list
$stmt = $conn->prepare("SELECT t.receipt_no, t.transaction_date, t.total_amount, t.payment_method, t.status, c.name AS customer, u.name AS staff FROM transactions t JOIN customers c ON c.id=t.customer_id JOIN users u ON u.id=t.staff_id WHERE t.transaction_date BETWEEN ? AND ? ORDER BY t.transaction_date, t.created_at");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// Top services
$stmt = $conn->prepare("SELECT td.service_name, SUM(td.qty) AS qty, SUM(td.subtotal) AS rev FROM transaction_details td JOIN transactions t ON t.id=td.transaction_id WHERE t.transaction_date BETWEEN ? AND ? AND t.status='completed' GROUP BY td.service_name ORDER BY rev DESC LIMIT 10");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$top_services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// Journal summary
$stmt = $conn->prepare("SELECT account_code, account_name, SUM(debit) AS td, SUM(credit) AS tc FROM journals WHERE journal_date BETWEEN ? AND ? GROUP BY account_code, account_name ORDER BY account_code");
$stmt->bind_param('ss', $date_from, $date_to); $stmt->execute();
$journal_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$conn->close();

$total_revenue = (float)$rev_summary['rev'];
$total_beban   = $total_expense + $total_purchase;
$laba_bersih   = $total_revenue - $total_beban;
$margin        = $total_revenue > 0 ? ($laba_bersih/$total_revenue)*100 : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Keuangan — <?= SALON_NAME ?></title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Helvetica Neue',Arial,sans-serif; font-size:10.5pt; color:#1a1218; background:#fff; }
.no-print { margin:16px 24px; display:flex; gap:10px; align-items:center; padding-bottom:16px; border-bottom:2px solid #f0e0dc; }
.btn-print { background:#c9636a; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:0.88rem; cursor:pointer; }
.btn-back  { background:#f0f0f0; color:#333; border:none; padding:10px 18px; border-radius:8px; font-size:0.88rem; cursor:pointer; text-decoration:none; display:inline-block; }
.page { max-width:900px; margin:0 auto; padding:30px 36px; }
.report-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #c9636a; padding-bottom:14px; margin-bottom:22px; }
.salon-name { font-size:20pt; font-weight:700; }
.salon-name span { color:#c9636a; }
.salon-info { font-size:8pt; color:#888; margin-top:3px; }
.report-meta { text-align:right; }
.report-title { font-size:12pt; font-weight:700; color:#c9636a; }
.report-period { font-size:8.5pt; color:#666; margin-top:3px; }
.section-title { font-size:9pt; font-weight:700; color:#c9636a; text-transform:uppercase; letter-spacing:0.08em; margin:20px 0 8px; padding-bottom:5px; border-bottom:1.5px solid #f0e0dc; }
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:8px; }
.kpi-box { border:1.5px solid #e8ddd6; border-radius:8px; padding:12px; text-align:center; }
.kpi-value { font-size:12pt; font-weight:700; color:#1a1218; }
.kpi-label { font-size:7pt; color:#999; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px; }
.pl-section { margin-bottom:4px; }
.pl-row { display:flex; justify-content:space-between; font-size:9pt; padding:5px 0; border-bottom:1px solid #f5ede8; }
.pl-row.subtotal { font-weight:700; background:#f5f5f5; padding:7px 8px; border-radius:5px; border:none; margin:4px 0; }
.pl-row.net { font-size:10.5pt; font-weight:700; padding:10px 10px; border-radius:8px; border:none; margin-top:8px; }
.pl-row.net.profit { background:#f0fdf4; color:#166534; }
.pl-row.net.loss   { background:#fef2f2; color:#b91c1c; }
table { width:100%; border-collapse:collapse; font-size:9pt; }
th { background:#fdf1ed; color:#666; text-transform:uppercase; font-size:7.5pt; letter-spacing:0.05em; padding:7px 9px; text-align:left; border-bottom:1.5px solid #e8ddd6; font-weight:600; }
td { padding:6px 9px; border-bottom:1px solid #f5ede8; }
tr:last-child td { border-bottom:none; }
tfoot td { background:#fdf1ed; font-weight:700; }
.text-right { text-align:right; }
.debit  { color:#166534; font-weight:600; text-align:right; }
.credit { color:#b91c1c; font-weight:600; text-align:right; }
.badge { padding:2px 7px; border-radius:20px; font-size:7pt; }
.badge-cash { background:#dcfce7; color:#166534; }
.badge-ewallet { background:#dbeafe; color:#1e40af; }
.badge-completed { background:#dcfce7; color:#166534; }
.badge-void { background:#fee2e2; color:#991b1b; }
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.report-footer { margin-top:32px; padding-top:10px; border-top:1px solid #e8ddd6; font-size:7.5pt; color:#aaa; display:flex; justify-content:space-between; }
@media print {
  .no-print { display:none!important; }
  body { font-size:9.5pt; }
  .page { padding:0; max-width:100%; }
  @page { margin:12mm 10mm; }
}
</style>
</head>
<body>

<div class="no-print">
  <a href="reports.php?from=<?= urlencode($date_from) ?>&to=<?= urlencode($date_to) ?>" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
  <span style="font-size:0.78rem;color:#888;">Tip: Di dialog cetak, pilih "Save as PDF" untuk export.</span>
</div>

<div class="page">
  <!-- Header -->
  <div class="report-header">
    <div>
      <div class="salon-name">MJ <span>Salon</span></div>
      <div class="salon-info"><?= htmlspecialchars(SALON_ADDR) ?></div>
      <div class="salon-info"><?= htmlspecialchars(SALON_TEL) ?> | <?= htmlspecialchars(SALON_EMAIL) ?></div>
    </div>
    <div class="report-meta">
      <div class="report-title">Laporan Keuangan</div>
      <div class="report-period">Periode: <?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?></div>
      <div class="report-period">Dicetak: <?= date('d M Y H:i') ?> oleh <?= htmlspecialchars($_SESSION['name']) ?></div>
    </div>
  </div>

  <!-- KPI -->
  <div class="section-title">Ringkasan Keuangan</div>
  <div class="kpi-grid">
    <div class="kpi-box"><div class="kpi-value">Rp <?= number_format($total_revenue,0,',','.') ?></div><div class="kpi-label">Total Pendapatan</div></div>
    <div class="kpi-box"><div class="kpi-value">Rp <?= number_format($total_beban,0,',','.') ?></div><div class="kpi-label">Total Beban</div></div>
    <div class="kpi-box"><div class="kpi-value" style="color:<?= $laba_bersih>=0?'#166534':'#b91c1c' ?>;">Rp <?= number_format(abs($laba_bersih),0,',','.') ?></div><div class="kpi-label"><?= $laba_bersih>=0?'Laba Bersih':'Rugi Bersih' ?></div></div>
    <div class="kpi-box"><div class="kpi-value"><?= number_format(abs($margin),1) ?>%</div><div class="kpi-label">Margin <?= $margin>=0?'Laba':'Rugi' ?></div></div>
  </div>

  <!-- Laporan Laba Rugi & Top Services side by side -->
  <div class="two-col" style="margin-top:16px;">
    <div>
      <div class="section-title">Laporan Laba / Rugi</div>
      <div class="pl-row" style="font-weight:600;color:#166534;">Pendapatan</div>
      <div class="pl-row"><span>Pendapatan Jasa (Cash)</span><span>Rp <?= number_format($rev_summary['cash_r'],0,',','.') ?></span></div>
      <div class="pl-row"><span>Pendapatan Jasa (E-Wallet)</span><span>Rp <?= number_format($rev_summary['ew_r'],0,',','.') ?></span></div>
      <div class="pl-row subtotal"><span>Total Pendapatan</span><span style="color:#166534;">Rp <?= number_format($total_revenue,0,',','.') ?></span></div>

      <div class="pl-row" style="font-weight:600;color:#b91c1c;margin-top:8px;">Beban / Biaya</div>
      <?php foreach ($exp_cats as $ec): ?>
      <div class="pl-row"><span>Biaya <?= htmlspecialchars($ec['category']) ?></span><span>Rp <?= number_format($ec['total'],0,',','.') ?></span></div>
      <?php endforeach; ?>
      <div class="pl-row"><span>Biaya Bahan & Perlengkapan</span><span>Rp <?= number_format($total_purchase,0,',','.') ?></span></div>
      <div class="pl-row subtotal"><span>Total Beban</span><span style="color:#b91c1c;">Rp <?= number_format($total_beban,0,',','.') ?></span></div>

      <div class="pl-row net <?= $laba_bersih>=0?'profit':'loss' ?>">
        <span><?= $laba_bersih>=0?'LABA BERSIH':'RUGI BERSIH' ?></span>
        <span>Rp <?= number_format(abs($laba_bersih),0,',','.') ?></span>
      </div>
    </div>
    <div>
      <div class="section-title">Top Layanan</div>
      <table>
        <thead><tr><th>#</th><th>Layanan</th><th class="text-right">Qty</th><th class="text-right">Pendapatan</th></tr></thead>
        <tbody>
          <?php foreach ($top_services as $i => $s): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($s['service_name']) ?></td>
            <td class="text-right"><?= $s['qty'] ?></td>
            <td class="text-right">Rp <?= number_format($s['rev'],0,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Transaction Register -->
  <div class="section-title">Register Transaksi Jasa</div>
  <table>
    <thead><tr><th>#</th><th>Receipt No.</th><th>Tanggal</th><th>Pelanggan</th><th>Staf</th><th>Metode</th><th class="text-right">Jumlah</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($transactions as $i => $row): ?>
      <tr>
        <td style="color:#aaa;"><?= $i+1 ?></td>
        <td style="font-family:monospace;font-size:8pt;"><?= htmlspecialchars($row['receipt_no']) ?></td>
        <td><?= date('d/m/Y', strtotime($row['transaction_date'])) ?></td>
        <td><?= htmlspecialchars($row['customer']) ?></td>
        <td><?= htmlspecialchars($row['staff']) ?></td>
        <td><span class="badge badge-<?= $row['payment_method'] ?>"><?= $row['payment_method']==='cash'?'Cash':'E-Wallet' ?></span></td>
        <td class="text-right" style="font-weight:600;">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="6">TOTAL (<?= count($transactions) ?> transaksi)</td>
      <td class="text-right">Rp <?= number_format(array_sum(array_column(array_filter($transactions, function($r){ return $r['status']==='completed'; }),'total_amount')),0,',','.') ?></td><td></td></tr>
    </tfoot>
  </table>

  <!-- Journal Summary -->
  <?php if (!empty($journal_summary)): ?>
  <div class="section-title">Ringkasan Buku Besar (Jurnal)</div>
  <table>
    <thead><tr><th>Kode</th><th>Nama Akun</th><th class="debit">Total Debit</th><th class="credit">Total Kredit</th><th class="text-right">Saldo</th></tr></thead>
    <tbody>
      <?php $gd=0; $gc=0;
      foreach ($journal_summary as $j): $gd+=$j['td']; $gc+=$j['tc']; ?>
      <tr>
        <td style="font-family:monospace;"><?= htmlspecialchars($j['account_code']) ?></td>
        <td><?= htmlspecialchars($j['account_name']) ?></td>
        <td class="debit"><?= $j['td']>0?'Rp '.number_format($j['td'],0,',','.'):'—' ?></td>
        <td class="credit"><?= $j['tc']>0?'Rp '.number_format($j['tc'],0,',','.'):'—' ?></td>
        <td class="text-right">Rp <?= number_format(abs($j['td']-$j['tc']),0,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="2">TOTAL</td>
        <td class="debit">Rp <?= number_format($gd,0,',','.') ?></td>
        <td class="credit">Rp <?= number_format($gc,0,',','.') ?></td>
        <td class="text-right" style="color:<?= round($gd,0)===round($gc,0)?'#166534':'#b91c1c' ?>;"><?= round($gd,0)===round($gc,0)?'✓ Balance':'✗ Tidak Balance' ?></td>
      </tr>
    </tfoot>
  </table>
  <?php endif; ?>

  <div class="report-footer">
    <span><?= htmlspecialchars(SALON_NAME) ?> — Accounting Information System</span>
    <span>Dicetak: <?= date('d-m-Y H:i:s') ?></span>
  </div>
</div>
<script>window.addEventListener('load', function(){ if(new URLSearchParams(location.search).get('autoprint')==='1') setTimeout(()=>window.print(),500); });</script>
</body></html>
