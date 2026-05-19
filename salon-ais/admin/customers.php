<?php
// ============================================================
// MJ Salon AIS - Customer Management (Admin)
// admin/customers.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Customers';
$conn = db_connect();

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = "%$search%";
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(t.id) AS txn_count, COALESCE(SUM(t.total_amount),0) AS lifetime_value
        FROM customers c
        LEFT JOIN transactions t ON t.customer_id = c.id AND t.status='completed'
        WHERE c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?
        GROUP BY c.id ORDER BY c.name
    ");
    $stmt->bind_param('sss', $like, $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(t.id) AS txn_count, COALESCE(SUM(t.total_amount),0) AS lifetime_value
        FROM customers c
        LEFT JOIN transactions t ON t.customer_id = c.id AND t.status='completed'
        GROUP BY c.id ORDER BY c.created_at DESC
    ");
}
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="section-card mb-4">
  <div class="section-card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="ais-label">Cari Pelanggan</label>
        <input type="text" name="q" class="ais-input" placeholder="Nama, telepon, atau email..."
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn-rose w-100" style="padding:10px;">
          <i class="bi bi-search me-1"></i>Search
        </button>
      </div>
      <div class="col-md-3">
        <a href="customers.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:10px;font-size:0.88rem;">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="section-card">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-people me-2" style="color:var(--rose)"></i>Customers (<?= count($customers) ?> data)</span>
  </div>
  <div style="overflow-x:auto;">
    <table class="ais-table">
      <thead>
        <tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Total Kunjungan</th><th>Total Transaksi</th><th>Terdaftar</th></tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px;">Tidak ada pelanggan ditemukan.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $i => $c): ?>
        <tr>
          <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['phone']) ?></td>
          <td style="color:var(--muted);font-size:0.82rem;"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
          <td>
            <span style="background:#f5ede8;color:var(--rose);padding:3px 10px;border-radius:20px;font-size:0.78rem;">
              <?= $c['txn_count'] ?> kunjungan
            </span>
          </td>
          <td style="font-weight:500;color:var(--rose);">Rp <?= number_format($c['lifetime_value'], 0, ',', '.') ?></td>
          <td style="font-size:0.78rem;color:var(--muted);"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
