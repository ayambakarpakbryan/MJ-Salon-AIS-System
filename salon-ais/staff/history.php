<?php
// ============================================================
// MJ Salon AIS - Staff Transaction History
// staff/history.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$page_title  = 'Riwayat Transaksi Saya';
$staff_id    = (int)$_SESSION['user_id'];
$conn        = db_connect();

$date_filter = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT t.*, c.name AS customer, c.phone
    FROM transactions t
    JOIN customers c ON c.id = t.customer_id
    WHERE t.staff_id = ? AND t.transaction_date = ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param('is', $staff_id, $date_filter);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$daily_revenue = array_sum(array_column(array_filter($rows, function($r){ return $r['status']==='completed'; }), 'total_amount'));

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Filter + Summary -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="section-card">
      <div class="section-card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
          <div class="flex-grow-1">
            <label class="ais-label">Tanggal</label>
            <input type="date" name="date" class="ais-input" value="<?= htmlspecialchars($date_filter) ?>"
                   max="<?= date('Y-m-d') ?>">
          </div>
          <button type="submit" class="btn-rose" style="padding:10px 20px;">
            <i class="bi bi-search me-1"></i>Tampilkan
          </button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card" style="height:100%;">
      <div class="stat-icon" style="background:#fce7ea;color:var(--rose);"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-value">Rp <?= number_format($daily_revenue, 0, ',', '.') ?></div>
      <div class="stat-label">Pendapatan Saya — <?= date('M j', strtotime($date_filter)) ?></div>
    </div>
  </div>
</div>

<div class="section-card">
  <div class="section-card-header">
    <span class="section-title">
      <i class="bi bi-clock-history me-2" style="color:var(--rose)"></i>
      Transaksi pada <?= date('F j, Y', strtotime($date_filter)) ?> — <?= count($rows) ?> data
    </span>
    <a href="transaction.php" class="btn-rose" style="font-size:0.78rem;padding:7px 14px;text-decoration:none;">
      <i class="bi bi-plus me-1"></i>Baru
    </a>
  </div>
  <div style="overflow-x:auto;">
    <table class="ais-table">
      <thead>
        <tr><th>Receipt #</th><th>Customer</th><th>Phone</th><th>Payment</th><th>Amount</th><th>Status</th><th>Time</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">
          <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
          Belum ada transaksi pada tanggal ini.
        </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><code style="font-size:0.77rem;"><?= htmlspecialchars($row['receipt_no']) ?></code></td>
          <td style="font-weight:500;"><?= htmlspecialchars($row['customer']) ?></td>
          <td style="color:var(--muted);font-size:0.82rem;"><?= htmlspecialchars($row['phone']) ?></td>
          <td><span class="badge-<?= $row['payment_method'] ?>"><?= ucfirst($row['payment_method']) ?></span></td>
          <td style="font-weight:500;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
          <td><span class="badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td style="font-size:0.8rem;color:var(--muted);"><?= date('g:i A', strtotime($row['created_at'])) ?></td>
          <td>
            <a href="receipt.php?receipt=<?= urlencode($row['receipt_no']) ?>"
               class="btn btn-sm" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>