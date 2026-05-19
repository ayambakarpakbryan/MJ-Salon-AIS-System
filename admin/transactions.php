<?php
// ============================================================
// MJ Salon AIS - All Transactions (Admin)
// admin/transactions.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Riwayat Transaksi';
$conn = db_connect();

$search = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= " AND (t.receipt_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types  .= 'sss';
}
if (in_array($filter_status, ['completed','refunded','void'])) {
    $where   .= " AND t.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}

$sql  = "SELECT t.*, c.name AS customer, c.phone, u.name AS staff
         FROM transactions t
         JOIN customers c ON c.id = t.customer_id
         JOIN users u ON u.id = t.staff_id
         $where
         ORDER BY t.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Filter Bar -->
<div class="section-card mb-4">
  <div class="section-card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="ais-label">Search</label>
        <input type="text" name="q" class="ais-input" placeholder="Receipt #, customer name or phone..."
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <label class="ais-label">Status</label>
        <select name="status" class="ais-input">
          <option value="">Semua Status</option>
          <option value="completed" <?= $filter_status==='completed'?'selected':'' ?>>Completed</option>
          <option value="void"      <?= $filter_status==='void'?'selected':'' ?>>Void</option>
          <option value="refunded"  <?= $filter_status==='refunded'?'selected':'' ?>>Refunded</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn-rose w-100" style="padding:10px;">
          <i class="bi bi-search me-1"></i>Search
        </button>
      </div>
      <div class="col-md-2">
        <a href="transactions.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:10px;font-size:0.88rem;">
          Clear
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="section-card">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-receipt me-2" style="color:var(--rose)"></i>Transactions (<?= count($rows) ?> records)</span>
  </div>
  <div style="overflow-x:auto;">
    <table class="ais-table">
      <thead>
        <tr><th>Receipt #</th><th>Date</th><th>Customer</th><th>Phone</th><th>Staff</th><th>Method</th><th>Amount</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px;">Tidak ada transaksi ditemukan.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><code style="font-size:0.77rem;"><?= htmlspecialchars($row['receipt_no']) ?></code></td>
          <td><?= htmlspecialchars($row['transaction_date']) ?></td>
          <td><?= htmlspecialchars($row['customer']) ?></td>
          <td style="font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($row['phone']) ?></td>
          <td><?= htmlspecialchars($row['staff']) ?></td>
          <td><span class="badge-<?= $row['payment_method'] ?>"><?= ucfirst($row['payment_method']) ?></span></td>
          <td style="font-weight:500;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
          <td><span class="badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td>
            <a href="../staff/receipt.php?receipt=<?= urlencode($row['receipt_no']) ?>"
               class="btn btn-sm" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;">
              <i class="bi bi-eye"></i>
            </a>
            <?php if ($row['status'] === 'completed'): ?>
            <button class="btn btn-sm ms-1" style="background:#fee2e2;color:#991b1b;border-radius:8px;font-size:0.75rem;"
                    onclick="voidTransaction('<?= htmlspecialchars($row['receipt_no']) ?>')"
                    title="Batalkan transaksi">
              <i class="bi bi-x-circle"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $extra_js = <<<JS
<script>
function voidTransaction(receiptNo) {
    const reason = prompt('Masukkan alasan pembatalan transaksi ini:\n(Receipt: ' + receiptNo + ')', 'Permintaan pelanggan');
    if (reason === null) return; // Cancelled
    if (!reason.trim()) { alert('Harap masukkan alasan.'); return; }

    if (!confirm('Are you sure you want to VOID transaction ' + receiptNo + '?\nTindakan ini akan membuat jurnal pembalik dan tidak dapat dibatalkan.')) return;

    fetch('../process/process_void.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'receipt_no=' + encodeURIComponent(receiptNo) + '&reason=' + encodeURIComponent(reason)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(() => showToast('Kesalahan jaringan. Silakan coba lagi.', 'error'));
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
