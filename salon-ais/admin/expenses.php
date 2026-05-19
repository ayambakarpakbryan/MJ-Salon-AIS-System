<?php
// ============================================================
// MJ Salon AIS - Daftar Pengeluaran Operasional
// admin/expenses.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Pengeluaran Operasional';
$conn = db_connect();

$date_from  = $_GET['from']     ?? date('Y-m-01');
$date_to    = $_GET['to']       ?? date('Y-m-d');
$category   = $_GET['category'] ?? '';
$search     = trim($_GET['q']   ?? '');

$where  = "WHERE e.expense_date BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types  = 'ss';

if ($category) {
    $where  .= " AND e.category = ?";
    $params[] = $category;
    $types   .= 's';
}
if ($search !== '') {
    $where  .= " AND (e.description LIKE ? OR e.expense_no LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like]);
    $types  .= 'ss';
}

$stmt = $conn->prepare("
    SELECT e.*, u.name AS staff_name
    FROM expenses e
    JOIN users u ON u.id = e.staff_id
    $where
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Category totals for breakdown
$stmt = $conn->prepare("
    SELECT category, SUM(total_amount) AS total
    FROM expenses
    WHERE expense_date BETWEEN ? AND ? AND status='completed'
    GROUP BY category ORDER BY total DESC
");
$stmt->bind_param('ss', $date_from, $date_to);
$stmt->execute();
$cat_totals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// KPIs - pakai foreach supaya kompatibel semua versi PHP
$total_expense = 0;
$total_count   = 0;
foreach ($rows as $row_kpi) {
    if ($row_kpi['status'] === 'completed') {
        $total_expense += (float)$row_kpi['total_amount'];
        $total_count++;
    }
}

// All categories for filter
$cats = $conn->query("SELECT DISTINCT category FROM expenses ORDER BY category")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fce7ea;color:var(--rose);"><i class="bi bi-cash-coin"></i></div>
      <div class="stat-value" style="font-size:1.35rem;">Rp <?= number_format($total_expense,0,',','.') ?></div>
      <div class="stat-label">Total Pengeluaran</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3d8;color:var(--gold);"><i class="bi bi-receipt"></i></div>
      <div class="stat-value"><?= $total_count ?></div>
      <div class="stat-label">Jumlah Transaksi</div>
    </div>
  </div>
  <?php foreach (array_slice($cat_totals, 0, 2) as $ct): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:#1e40af;"><i class="bi bi-tag"></i></div>
      <div class="stat-value" style="font-size:1.2rem;">Rp <?= number_format($ct['total'],0,',','.') ?></div>
      <div class="stat-label"><?= htmlspecialchars($ct['category']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="section-card mb-4">
  <div class="section-card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="ais-label">Dari Tanggal</label>
        <input type="date" name="from" class="ais-input" value="<?= htmlspecialchars($date_from) ?>">
      </div>
      <div class="col-md-2">
        <label class="ais-label">Sampai Tanggal</label>
        <input type="date" name="to" class="ais-input" value="<?= htmlspecialchars($date_to) ?>">
      </div>
      <div class="col-md-2">
        <label class="ais-label">Kategori</label>
        <select name="category" class="ais-input">
          <option value="">Semua Kategori</option>
          <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category===$c['category']?'selected':'' ?>>
            <?= htmlspecialchars($c['category']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="ais-label">Cari</label>
        <input type="text" name="q" class="ais-input" placeholder="Deskripsi, nomor..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn-rose w-100" style="padding:10px;">
          <i class="bi bi-search me-1"></i>Filter
        </button>
      </div>
      <div class="col-md-1">
        <a href="expenses.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;padding:10px;font-size:0.88rem;">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <!-- Main Table -->
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-cash-coin me-2" style="color:var(--rose)"></i>Daftar Pengeluaran (<?= count($rows) ?> data)</span>
        <a href="expense_form.php" class="btn-rose" style="font-size:0.78rem;padding:7px 16px;text-decoration:none;">
          <i class="bi bi-plus me-1"></i>Tambah
        </a>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr><th>No.</th><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">Belum ada data pengeluaran.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <tr>
              <td><code style="font-size:0.72rem;"><?= htmlspecialchars($row['expense_no']) ?></code></td>
              <td><?= date('d/m/Y', strtotime($row['expense_date'])) ?></td>
              <td>
                <?php
                $cat_colors = [
                  'Gaji'      => '#fce7ea:var(--rose)',
                  'Utilitas'  => '#dbeafe:#1e40af',
                  'Marketing' => '#dcfce7:#166534',
                  'Perawatan' => '#f3e8ff:#7e22ce',
                  'Lain-lain' => '#f1f5f9:#475569',
                ];
                $cc = $cat_colors[$row['category']] ?? '#f1f5f9:#475569';
                $parts = explode(':', $cc);
                $bg    = $parts[0];
                $color = $parts[1];
                ?>
                <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:3px 10px;border-radius:20px;font-size:0.73rem;font-weight:500;">
                  <?= htmlspecialchars($row['category']) ?>
                </span>
              </td>
              <td style="font-size:0.83rem;"><?= htmlspecialchars($row['description']) ?></td>
              <td>
                <span class="<?= $row['payment_method']==='cash'?'badge-cash':'badge-ewallet' ?>">
                  <?= $row['payment_method']==='cash'?'Cash':'Transfer' ?>
                </span>
              </td>
              <td style="font-weight:600;">Rp <?= number_format($row['total_amount'],0,',','.') ?></td>
              <td>
                <span class="<?= $row['status']==='completed'?'badge-completed':'badge-void' ?>">
                  <?= $row['status']==='completed'?'Selesai':'Dibatalkan' ?>
                </span>
              </td>
              <td>
                <a href="expense_detail.php?id=<?= $row['id'] ?>"
                   class="btn btn-sm" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (!empty($rows)): ?>
          <tfoot>
            <tr style="background:#fdf8f2;">
              <td colspan="5" style="font-weight:700;padding:12px 14px;">TOTAL PERIODE</td>
              <td style="font-weight:700;color:var(--rose);padding:12px 14px;">Rp <?= number_format($total_expense,0,',','.') ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- Category Breakdown -->
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-pie-chart me-2" style="color:var(--gold)"></i>Per Kategori</span>
      </div>
      <div class="section-card-body">
        <?php foreach ($cat_totals as $ct): ?>
        <?php
        $pct   = $total_expense > 0 ? ($ct['total'] / $total_expense) * 100 : 0;
        $cc2   = $cat_colors[$ct['category']] ?? '#f1f5f9:#475569';
        $pts2  = explode(':', $cc2);
        $bg2   = $pts2[0];
        $col2  = $pts2[1];
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size:0.82rem;font-weight:500;"><?= htmlspecialchars($ct['category']) ?></span>
            <span style="font-size:0.78rem;color:var(--muted);">Rp <?= number_format($ct['total'],0,',','.') ?></span>
          </div>
          <div style="background:#f0e8e1;border-radius:20px;height:6px;overflow:hidden;">
            <div style="width:<?= round($pct) ?>%;height:100%;background:<?= $col2 ?>;border-radius:20px;"></div>
          </div>
          <div style="font-size:0.7rem;color:var(--muted);margin-top:2px;"><?= number_format($pct,1) ?>% dari total</div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($cat_totals)): ?>
        <p style="color:var(--muted);font-size:0.83rem;">Belum ada data.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
