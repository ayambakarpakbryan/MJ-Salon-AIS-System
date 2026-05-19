<?php
// ============================================================
// MJ Salon AIS - Layanans Management (Admin)
// admin/services.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Layanans';
$conn = db_connect();

// Handle Add/Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $svc_id   = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name']     ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 1);

    if ($action === 'add' && $name && $category && $price > 0) {
        $s = $conn->prepare("INSERT INTO services (name,category,price,is_active) VALUES (?,?,?,?)");
        $s->bind_param('ssdi', $name, $category, $price, $is_active);
        $s->execute(); $s->close();
        set_flash('success', "Layanan '$name' berhasil ditambahkan.");
    } elseif ($action === 'edit' && $svc_id > 0 && $name && $category && $price > 0) {
        $s = $conn->prepare("UPDATE services SET name=?,category=?,price=?,is_active=? WHERE id=?");
        $s->bind_param('ssdii', $name, $category, $price, $is_active, $svc_id);
        $s->execute(); $s->close();
        set_flash('success', "Layanan berhasil diperbarui.");
    } elseif ($action === 'toggle' && $svc_id > 0) {
        $conn->query("UPDATE services SET is_active = NOT is_active WHERE id=$svc_id");
        set_flash('success', 'Layanan status berhasil diperbarui.');
    }
    header('Location: services.php'); exit;
}

$services = $conn->query("SELECT * FROM services ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
$categories = array_unique(array_column($services, 'category'));

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-3">
  <!-- Tambah Layanan Form -->
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-plus-circle me-2" style="color:var(--rose)"></i>Tambah Layanan</span>
      </div>
      <div class="section-card-body">
        <form method="POST" action="">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="ais-label">Nama Layanan <span style="color:var(--rose)">*</span></label>
            <input type="text" name="name" class="ais-input" placeholder="contoh: Haircut (Short)" required>
          </div>
          <div class="mb-3">
            <label class="ais-label">Kategori <span style="color:var(--rose)">*</span></label>
            <input type="text" name="category" class="ais-input" placeholder="Hair / Nails / Facial..." list="cat_list" required>
            <datalist id="cat_list">
              <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="mb-3">
            <label class="ais-label">Harga (Rp ) <span style="color:var(--rose)">*</span></label>
            <input type="number" name="price" class="ais-input" placeholder="0.00" min="1" step="0.01" required>
          </div>
          <div class="mb-3">
            <label class="ais-label">Status</label>
            <select name="is_active" class="ais-input">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button type="submit" class="btn-rose w-100" style="padding:10px;">
            <i class="bi bi-plus me-2"></i>Tambah Layanan
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Layanans List -->
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-scissors me-2" style="color:var(--rose)"></i>Semua Layanan (<?= count($services) ?>)</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr><th>#</th><th>Name</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($services as $i => $svc): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
              <td style="font-weight:500;"><?= htmlspecialchars($svc['name']) ?></td>
              <td>
                <span style="background:#f5ede8;color:var(--rose);padding:3px 10px;border-radius:20px;font-size:0.75rem;">
                  <?= htmlspecialchars($svc['category']) ?>
                </span>
              </td>
              <td style="font-weight:500;">Rp <?= number_format($svc['price'], 0, ',', '.') ?></td>
              <td>
                <?php if ($svc['is_active']): ?>
                <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;">Aktif</span>
                <?php else: ?>
                <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:0.75rem;">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;"
                        onclick="editLayanan(<?= htmlspecialchars(json_encode($svc)) ?>)"
                        title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $svc['id'] ?>">
                  <button type="submit" class="btn btn-sm ms-1"
                          style="background:#f0f0f0;color:#555;border-radius:8px;font-size:0.75rem;"
                          title="Ubah Status">
                    <i class="bi bi-toggle-<?= $svc['is_active'] ? 'on text-success' : 'off text-danger' ?>"></i>
                  </button>
                </form>
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
  <div class="modal-dialog">
    <form method="POST" class="modal-content" style="border-radius:14px;border:1px solid #ede4dd;">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header" style="border-bottom:1px solid #ede4dd;">
        <h5 class="modal-title" style="font-family:'Playfair Display',serif;">Edit Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <div class="mb-3">
          <label class="ais-label">Name</label>
          <input type="text" name="name" id="edit_name" class="ais-input" required>
        </div>
        <div class="mb-3">
          <label class="ais-label">Kategori</label>
          <input type="text" name="category" id="edit_category" class="ais-input" required>
        </div>
        <div class="mb-3">
          <label class="ais-label">Harga (Rp )</label>
          <input type="number" name="price" id="edit_price" class="ais-input" step="0.01" required>
        </div>
        <div class="mb-3">
          <label class="ais-label">Status</label>
          <select name="is_active" id="edit_status" class="ais-input">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #ede4dd;gap:10px;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn-rose" style="padding:9px 24px;">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php $extra_js = <<<JS
<script>
function editLayanan(svc) {
    document.getElementById('edit_id').value       = svc.id;
    document.getElementById('edit_name').value     = svc.name;
    document.getElementById('edit_category').value = svc.category;
    document.getElementById('edit_price').value    = svc.price;
    document.getElementById('edit_status').value   = svc.is_active;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
