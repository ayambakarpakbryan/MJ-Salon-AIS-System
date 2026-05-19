<?php
// ============================================================
// MJ Salon AIS - Receipt / Invoice View
// staff/receipt.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$receipt_no = trim($_GET['receipt'] ?? '');

// INTERNAL CONTROL: Validate receipt number provided
if ($receipt_no === '') {
    header('Location: transaction.php'); exit;
}

$conn = db_connect();

// Fetch transaction with customer and staff info
$stmt = $conn->prepare("
    SELECT t.*, c.name AS customer_name, c.phone AS customer_phone,
           u.name AS staff_name
    FROM transactions t
    JOIN customers c ON c.id = t.customer_id
    JOIN users u     ON u.id = t.staff_id
    WHERE t.receipt_no = ?
    LIMIT 1
");
$stmt->bind_param('s', $receipt_no);
$stmt->execute();
$txn = $stmt->get_result()->fetch_assoc();
$stmt->close();

// INTERNAL CONTROL: Receipt must exist
if (!$txn) {
    die('<p style="padding:40px;font-family:sans-serif;">Receipt not found.</p>');
}

// Fetch line items
$stmt = $conn->prepare("SELECT * FROM transaction_details WHERE transaction_id=? ORDER BY id");
$stmt->bind_param('i', $txn['id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt — <?= htmlspecialchars($receipt_no) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --rose:#c9636a; --gold:#d4a84b; }
body { background: #f5ede8; font-family: 'DM Sans', sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 30px 16px; }
.receipt-wrapper { width: 100%; max-width: 520px; }
.receipt-actions { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.btn-action { padding: 10px 18px; border-radius: 10px; font-size: 0.85rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-print { background: var(--rose); color: #fff; }
.btn-new   { background: #1a1218; color: #fff; }
.btn-dash  { background: #fff; color: #1a1218; border: 1.5px solid #ede4dd; }
/* Receipt card */
.receipt-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
.receipt-header { background: linear-gradient(135deg, #1a1218, #3d2a34); padding: 28px; text-align: center; color: #fff; }
.salon-name { font-family: 'Playfair Display', serif; font-size: 1.8rem; }
.salon-name span { color: var(--gold); }
.salon-tagline { font-size: 0.7rem; letter-spacing: 0.15em; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-top: 2px; }
.receipt-no-badge { display: inline-block; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.9); font-size: 0.75rem; padding: 4px 14px; border-radius: 20px; margin-top: 12px; letter-spacing: 0.06em; font-family: monospace; }
.receipt-body { padding: 24px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; margin-bottom: 18px; }
.info-row { font-size: 0.8rem; }
.info-row .lbl { color: #999; display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; }
.info-row .val { color: #1a1218; font-weight: 500; }
.divider { border: none; border-top: 1.5px dashed #e8ddd6; margin: 16px 0; }
.items-table { width: 100%; font-size: 0.84rem; }
.items-table th { color: #999; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; padding-bottom: 8px; font-weight: 500; }
.items-table td { padding: 7px 0; border-bottom: 1px solid #f5f0eb; }
.items-table tr:last-child td { border-bottom: none; }
.totals-row { display: flex; justify-content: space-between; font-size: 0.86rem; padding: 5px 0; color: #555; }
.totals-row.grand { font-size: 1rem; font-weight: 700; color: #1a1218; padding-top: 10px; margin-top: 4px; border-top: 2px solid #1a1218; }
.totals-row.grand .amt { color: var(--rose); }
.payment-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; margin-left: 6px; }
.pay-cash    { background: #dcfce7; color: #166534; }
.pay-ewallet { background: #dbeafe; color: #1e40af; }
.receipt-footer { background: #fdf8f2; padding: 16px; text-align: center; font-size: 0.75rem; color: #999; border-top: 1px solid #f0e8e0; }
.journal-section { background: #f5ede8; border-radius: 10px; padding: 14px; margin-top: 18px; }
.journal-section .j-title { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--rose); margin-bottom: 10px; }
.j-entry { display: grid; grid-template-columns: 60px 1fr 80px 80px; gap: 4px; font-size: 0.76rem; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.j-entry:last-child { border-bottom: none; }
.j-code { color: #999; font-family: monospace; }
.j-name { color: #1a1218; }
.j-debit  { color: #166534; font-weight: 500; text-align: right; }
.j-credit { color: #b91c1c; font-weight: 500; text-align: right; }
.j-header { font-size: 0.68rem; color: #aaa; text-transform: uppercase; letter-spacing: 0.06em; }
@media print {
  body { background: #fff; padding: 0; }
  .receipt-actions, .journal-section { display: none !important; }
  .receipt-card { box-shadow: none; border-radius: 0; }
  .receipt-wrapper { max-width: 100%; }
}
</style>
</head>
<body>

<div class="receipt-wrapper">

  <!-- Action Buttons -->
  <div class="receipt-actions no-print">
    <button class="btn-action btn-print" onclick="window.print()">
      <i class="bi bi-printer"></i> Cetak Struk
    </button>
    <a href="transaction.php" class="btn-action btn-new">
      <i class="bi bi-plus-circle"></i> Transaksi Baru
    </a>
    <?php if (is_admin()): ?>
    <a href="../admin/dashboard.php" class="btn-action btn-dash">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <?php endif; ?>
  </div>

  <!-- Receipt Card -->
  <div class="receipt-card">
    <!-- Header -->
    <div class="receipt-header">
      <div class="salon-name">MJ <span>Salon</span></div>
      <div class="salon-tagline">Where Beauty Meets Excellence</div>
      <div style="font-size:0.72rem;color:rgba(255,255,255,0.45);margin-top:6px;">
        <?= htmlspecialchars(SALON_ADDR) ?> | <?= htmlspecialchars(SALON_TEL) ?>
      </div>
      <div class="receipt-no-badge"><?= htmlspecialchars($txn['receipt_no']) ?></div>
    </div>

    <!-- Body -->
    <div class="receipt-body">
      <div class="info-grid">
        <div class="info-row">
          <span class="lbl">Date</span>
          <span class="val"><?= date('F j, Y', strtotime($txn['transaction_date'])) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Time</span>
          <span class="val"><?= date('g:i A', strtotime($txn['created_at'])) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Customer</span>
          <span class="val"><?= htmlspecialchars($txn['customer_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Phone</span>
          <span class="val"><?= htmlspecialchars($txn['customer_phone'] ?: '—') ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Dilayani Oleh</span>
          <span class="val"><?= htmlspecialchars($txn['staff_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Payment</span>
          <span class="val">
            <span class="payment-badge pay-<?= $txn['payment_method'] ?>">
              <?= ucfirst($txn['payment_method']) ?>
            </span>
          </span>
        </div>
      </div>

      <hr class="divider">

      <!-- Line Items -->
      <table class="items-table">
        <thead>
          <tr>
            <th style="text-align:left;">Service</th>
            <th style="text-align:right;">Qty</th>
            <th style="text-align:right;">Price</th>
            <th style="text-align:right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['service_name']) ?></td>
            <td style="text-align:right;color:#777;"><?= $item['qty'] ?></td>
            <td style="text-align:right;">Rp <?= number_format($item['unit_price'], 0, ',', '.') ?></td>
            <td style="text-align:right;font-weight:500;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <hr class="divider">

      <!-- Totals -->
      <div class="totals-row">
        <span>Subtotal</span>
        <span>Rp <?= number_format($txn['subtotal'], 0, ',', '.') ?></span>
      </div>
      <?php if ($txn['discount'] > 0): ?>
      <div class="totals-row">
        <span>Diskon</span>
        <span style="color:#166534;">−Rp <?= number_format($txn['discount'], 0, ',', '.') ?></span>
      </div>
      <?php endif; ?>
      <div class="totals-row grand">
        <span>TOTAL</span>
        <span class="amt">Rp <?= number_format($txn['total_amount'], 0, ',', '.') ?></span>
      </div>
      <?php if ($txn['payment_method'] === 'cash'): ?>
      <div class="totals-row" style="margin-top:8px;">
        <span>Jumlah Bayar</span>
        <span>Rp <?= number_format($txn['amount_paid'], 0, ',', '.') ?></span>
      </div>
      <div class="totals-row">
        <span>Kembalian</span>
        <span>Rp <?= number_format($txn['change_amount'], 0, ',', '.') ?></span>
      </div>
      <?php endif; ?>

      <?php if ($txn['notes']): ?>
      <div style="margin-top:14px;padding:10px;background:#fdf8f2;border-radius:8px;font-size:0.8rem;color:#666;">
        <strong>Catatan:</strong> <?= htmlspecialchars($txn['notes']) ?>
      </div>
      <?php endif; ?>

      <!-- AIS Journal Entries (shown on screen, hidden on print) -->
      <div class="journal-section no-print">
        <div class="j-title"><i class="bi bi-journal-text me-1"></i>Jurnal Akuntansi (Otomatis)</div>
        <div class="j-entry j-header">
          <span>Code</span><span>Account</span><span style="text-align:right;">Debit</span><span style="text-align:right;">Credit</span>
        </div>
        <?php
        $conn2 = db_connect();
        $jstmt = $conn2->prepare("SELECT * FROM journals WHERE ref_type='transaction' AND ref_id=? ORDER BY id");
        $jstmt->bind_param('i', $txn['id']);
        $jstmt->execute();
        $jentries = $jstmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $jstmt->close();
        $conn2->close();
        foreach ($jentries as $j):
        ?>
        <div class="j-entry">
          <span class="j-code"><?= htmlspecialchars($j['account_code']) ?></span>
          <span class="j-name"><?= htmlspecialchars($j['account_name']) ?></span>
          <span class="j-debit"><?= $j['debit'] > 0 ? 'Rp '.number_format($j['debit'], 0, ',', '.') : '—' ?></span>
          <span class="j-credit"><?= $j['credit'] > 0 ? 'Rp '.number_format($j['credit'], 0, ',', '.') : '—' ?></span>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- Footer -->
    <div class="receipt-footer">
      <strong>Terima kasih telah mengunjungi <?= SALON_NAME ?>!</strong><br>
      <?= SALON_EMAIL ?> | Struk ini dicetak oleh sistem.
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
