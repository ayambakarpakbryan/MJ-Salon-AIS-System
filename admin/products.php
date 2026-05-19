<?php
// ============================================================
// MJ Salon AIS - Manajemen Produk & Stok
// admin/products.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();
$page_title = 'Produk & Stok';
$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['pid'] ?? 0);

    if ($action === 'add') {
        $name      = trim($_POST['name'] ?? '');
        $category  = trim($_POST['category'] ?? '');
        $unit      = trim($_POST['unit'] ?? 'pcs');
        $stock     = (int)($_POST['stock'] ?? 0);
        $min_stock = (int)($_POST['min_stock'] ?? 5);
        if ($name && $category) {
            $conn->query("INSERT INTO products (name,category,unit,stock,min_stock) VALUES ('".
                $conn->real_escape_string($name)."','".
                $conn->real_escape_string($category)."','".
                $conn->real_escape_string($unit)."',$stock,$min_stock)");
            set_flash('success', "Produk \"$name\" berhasil ditambahkan.");
        }
    } elseif ($action === 'edit' && $pid) {
        $name      = trim($_POST['name'] ?? '');
        $category  = trim($_POST['category'] ?? '');
        $unit      = trim($_POST['unit'] ?? 'pcs');
        $min_stock = (int)($_POST['min_stock'] ?? 5);
        $is_active = (int)($_POST['is_active'] ?? 1);
        $conn->query("UPDATE products SET name='".$conn->real_escape_string($name)."',
            category='".$conn->real_escape_string($category)."',
            unit='".$conn->real_escape_string($unit)."',
            min_stock=$min_stock, is_active=$is_active WHERE id=$pid");
        set_flash('success', 'Produk berhasil diperbarui.');
    } elseif ($action === 'adjust' && $pid) {
        $adjust = (int)($_POST['adjust'] ?? 0);
        $conn->query("UPDATE products SET stock = GREATEST(0, stock + $adjust) WHERE id=$pid");
        set_flash('success', 'Stok berhasil diperbarui.');
    }
    header('Location: products.php'); exit;
}

$products   = $conn->query("SELECT * FROM products ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
$categories = array_unique(array_column($products, 'category'));
$low_stock  = array_filter($products, function($p){ return $p['stock'] <= $p['min_stock'] && $p['is_active']; });
$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<?php if (!empty($low_stock)): ?>
<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.83rem;color:#92400e;">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <strong>Peringatan Stok Rendah:</strong>
  <?= implode(', ', array_map(function($p){ return htmlspecialchars($p['name']).' ('.$p['stock'].' '.$p['unit'].')'; }, $low_stock)) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <!-- Add Form -->
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-plus-circle me-2" style="color:var(--rose)"></i>Tambah Produk</span>
      </div>
      <div class="section-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="ais-label">Nama Produk *</label>
            <input type="text" name="name" class="ais-input" placeholder="Nama barang" required>
          </div>
          <div class="mb-3">
            <label class="ais-label">Kategori *</label>
            <input type="text" name="category" class="ais-input" placeholder="Contoh: Produk Rambut" list="cat_list" required>
            <datalist id="cat_list">
              <?php foreach ($categories as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="ais-label">Satuan</label>
              <input type="text" name="unit" class="ais-input" placeholder="pcs/botol/dll" value="pcs">
            </div>
            <div class="col-6">
              <label class="ais-label">Stok Awal</label>
              <input type="number" name="stock" class="ais-input" value="0" min="0">
            </div>
          </div>
          <div class="mb-4">
            <label class="ais-label">Stok Minimum</label>
            <input type="number" name="min_stock" class="ais-input" value="5" min="1">
          </div>
          <button type="submit" class="btn-rose w-100" style="padding:10px;">
            <i class="bi bi-plus me-2"></i>Tambah Produk
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Product Table -->
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-box-seam me-2" style="color:var(--rose)"></i>Daftar Produk (<?= count($products) ?>)</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr><th>#</th><th>Nama Produk</th><th>Kategori</th><th>Satuan</th><th>Stok</th><th>Min</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($products as $i => $p): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
              <td style="font-weight:500;"><?= htmlspecialchars($p['name']) ?></td>
              <td style="font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($p['category']) ?></td>
              <td><?= htmlspecialchars($p['unit']) ?></td>
              <td>
                <span style="font-weight:600;color:<?= $p['stock'] <= $p['min_stock'] ? '#b91c1c' : '#166534' ?>;">
                  <?= $p['stock'] ?>
                </span>
                <?php if ($p['stock'] <= $p['min_stock'] && $p['is_active']): ?>
                  <span style="font-size:0.65rem;background:#fee2e2;color:#b91c1c;padding:1px 6px;border-radius:10px;margin-left:4px;">Rendah</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--muted);font-size:0.82rem;"><?= $p['min_stock'] ?></td>
              <td>
                <?php if ($p['is_active']): ?>
                <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.72rem;">Aktif</span>
                <?php else: ?>
                <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:0.72rem;">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm me-1" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.72rem;"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:8px;font-size:0.72rem;"
                        onclick="openAdjust(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['stock'] ?>)">
                  <i class="bi bi-plus-slash-minus"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><form method="POST" class="modal-content">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="pid" id="edit_pid">
    <div class="modal-header" style="border-bottom:1px solid #ede4dd;">
      <h5 class="modal-title" style="font-family:'Playfair Display',serif;">Edit Produk</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <div class="mb-3"><label class="ais-label">Nama</label><input type="text" name="name" id="edit_name" class="ais-input" required></div>
      <div class="mb-3"><label class="ais-label">Kategori</label><input type="text" name="category" id="edit_category" class="ais-input" required></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="ais-label">Satuan</label><input type="text" name="unit" id="edit_unit" class="ais-input"></div>
        <div class="col-6"><label class="ais-label">Stok Minimum</label><input type="number" name="min_stock" id="edit_min_stock" class="ais-input" min="0"></div>
      </div>
      <div class="mb-3"><label class="ais-label">Status</label>
        <select name="is_active" id="edit_is_active" class="ais-input">
          <option value="1">Aktif</option><option value="0">Nonaktif</option>
        </select>
      </div>
    </div>
    <div class="modal-footer" style="border-top:1px solid #ede4dd;gap:8px;">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
      <button type="submit" class="btn-rose" style="padding:8px 22px;">Simpan</button>
    </div>
  </form></div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><form method="POST" class="modal-content">
    <input type="hidden" name="action" value="adjust">
    <input type="hidden" name="pid" id="adj_pid">
    <div class="modal-header" style="border-bottom:1px solid #ede4dd;">
      <h5 class="modal-title" style="font-family:'Playfair Display',serif;font-size:1rem;">Sesuaikan Stok</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <div style="font-size:0.85rem;color:var(--muted);margin-bottom:12px;" id="adj_info"></div>
      <label class="ais-label">Tambah / Kurang (+/-)</label>
      <input type="number" name="adjust" id="adj_value" class="ais-input" placeholder="Contoh: +10 atau -3" required>
      <div style="font-size:0.75rem;color:var(--muted);margin-top:6px;">Gunakan angka positif untuk tambah stok, negatif untuk kurangi.</div>
    </div>
    <div class="modal-footer" style="border-top:1px solid #ede4dd;gap:8px;">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
      <button type="submit" class="btn-rose" style="padding:8px 22px;">Simpan</button>
    </div>
  </form></div>
</div>

<?php $extra_js = <<<JS
<script>
function openEdit(p) {
    document.getElementById('edit_pid').value       = p.id;
    document.getElementById('edit_name').value      = p.name;
    document.getElementById('edit_category').value  = p.category;
    document.getElementById('edit_unit').value      = p.unit;
    document.getElementById('edit_min_stock').value = p.min_stock;
    document.getElementById('edit_is_active').value = p.is_active;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
function openAdjust(id, name, stock) {
    document.getElementById('adj_pid').value  = id;
    document.getElementById('adj_info').textContent = name + ' — Stok saat ini: ' + stock;
    document.getElementById('adj_value').value = '';
    new bootstrap.Modal(document.getElementById('adjustModal')).show();
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>