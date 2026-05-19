<?php
// ============================================================
// MJ Salon AIS - Daftar Pembelian Barang (Admin)
// admin/purchasing.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Pembelian Barang';
$conn = db_connect();

$search        = trim($_GET['q']      ?? '');
$filter_method = $_GET['method']      ?? '';
$date_from     = $_GET['from']        ?? date('Y-m-01');
$date_to       = $_GET['to']          ?? date('Y-m-d');

$where  = "WHERE p.purchase_date BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types  = 'ss';

if ($search !== '') {
    $where  .= " AND (p.purchase_no LIKE ? OR s.name LIKE ? OR p.notes LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= 'sss';
}
if (in_array($filter_method, ['cash','transfer','kredit'])) {
    $where  .= " AND p.payment_method = ?";
    $params[] = $filter_method;
    $types   .= 's';
}

$stmt = $conn->prepare("
    SELECT p.*, COALESCE(s.name,'(Tanpa Supplier)') AS supplier_name, u.name AS staff_name
    FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    JOIN users u ON u.id = p.staff_id
    $where
    ORDER BY p.purchase_date DESC, p.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// KPI
$total_purchase = 0;
$total_count    = 0;
foreach ($rows as $_r) {
    if ($_r['status'] === 'completed') {
        $total_purchase += (float)$_r['total_amount'];
        $total_count++;
    }
}

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fce7ea;color:var(--rose);"><i class="bi bi-cart3"></i></div>
      <div class="stat-value">Rp <?= number_format($total_purchase,0,',','.') ?></div>
      <div class="stat-label">Total Pembelian</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dcfce7;color:#166534;"><i class="bi bi-receipt-cutoff"></i></div>
      <div class="stat-value"><?= $total_count ?></div>
      <div class="stat-label">Transaksi Pembelian</div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="section-card mb-4">
  <div class="section-card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="ais-label">Dari Tanggal</label>
        <input type="date" name="from" class="ais-input" value="<?= htmlspecialchars($date_from) ?>">
      </div>
      <div class="col-md-3">
        <label class="ais-label">Sampai Tanggal</label>
        <input type="date" name="to" class="ais-input" value="<?= htmlspecialchars($date_to) ?>">
      </div>
      <div class="col-md-2">
        <label class="ais-label">Metode Bayar</label>
        <select name="method" class="ais-input">
          <option value="">Semua</option>
          <option value="cash"     <?= $filter_method==='cash'?'selected':'' ?>>Cash</option>
          <option value="transfer" <?= $filter_method==='transfer'?'selected':'' ?>>Transfer</option>
          <option value="kredit"   <?= $filter_method==='kredit'?'selected':'' ?>>Kredit</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="ais-label">Cari</label>
        <input type="text" name="q" class="ais-input" placeholder="No. PO, supplier..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn-rose w-100" style="padding:10px;"><i class="bi bi-search"></i></button>
      </div>
      <div class="col-md-1">
        <a href="purchasing.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:10px;font-size:0.88rem;">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="section-card">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-cart3 me-2" style="color:var(--rose)"></i>Daftar Pembelian (<?= count($rows) ?> data)</span>
    <a href="purchase_form.php" class="btn-rose" style="font-size:0.78rem;padding:7px 16px;text-decoration:none;">
      <i class="bi bi-plus me-1"></i>Tambah Pembelian
    </a>
  </div>
  <div style="overflow-x:auto;">
    <table class="ais-table">
      <thead>
        <tr><th>No. PO</th><th>Tanggal</th><th>Supplier</th><th>Oleh</th><th>Metode</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">Belum ada data pembelian.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><code style="font-size:0.77rem;"><?= htmlspecialchars($row['purchase_no']) ?></code></td>
          <td><?= date('d/m/Y', strtotime($row['purchase_date'])) ?></td>
          <td><?= htmlspecialchars($row['supplier_name']) ?></td>
          <td style="font-size:0.82rem;color:var(--muted);"><?= htmlspecialchars($row['staff_name']) ?></td>
          <td>
            <?php
            $mc = ['cash'=>'badge-cash','transfer'=>'badge-ewallet','kredit'=>'badge-refunded'];
            $ml = ['cash'=>'Cash','transfer'=>'Transfer','kredit'=>'Kredit'];
            ?>
            <span class="<?= $mc[$row['payment_method']] ?? '' ?>"><?= $ml[$row['payment_method']] ?? '-' ?></span>
          </td>
          <td style="font-weight:600;">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
          <td>
            <span class="<?= $row['status']==='completed' ? 'badge-completed' : 'badge-void' ?>">
              <?= $row['status']==='completed' ? 'Selesai' : 'Dibatalkan' ?>
            </span>
          </td>
          <td>
            <a href="purchase_detail.php?id=<?= $row['id'] ?>"
               class="btn btn-sm" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;">
              <i class="bi bi-eye"></i> Detail
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
      <tfoot>
        <tr style="background:#fdf8f2;">
          <td colspan="5" style="font-weight:600;padding:11px 14px;">TOTAL PERIODE</td>
          <td style="font-weight:700;color:var(--rose);padding:11px 14px;">Rp <?= number_format($total_purchase,0,',','.') ?></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>