<?php
// ============================================================
// MJ Salon AIS - Manajemen Supplier
// admin/suppliers.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();
$page_title = 'Supplier';
$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $sid     = (int)($_POST['sid'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($action === 'add' && $name) {
        $n = $conn->real_escape_string($name);
        $p = $conn->real_escape_string($phone);
        $a = $conn->real_escape_string($address);
        $conn->query("INSERT INTO suppliers (name,phone,address) VALUES ('$n','$p','$a')");
        set_flash('success', "Supplier \"$name\" ditambahkan.");
    } elseif ($action === 'edit' && $sid && $name) {
        $n = $conn->real_escape_string($name);
        $p = $conn->real_escape_string($phone);
        $a = $conn->real_escape_string($address);
        $is_active = (int)($_POST['is_active'] ?? 1);
        $conn->query("UPDATE suppliers SET name='$n',phone='$p',address='$a',is_active=$is_active WHERE id=$sid");
        set_flash('success', 'Supplier diperbarui.');
    } elseif ($action === 'toggle' && $sid) {
        $conn->query("UPDATE suppliers SET is_active = NOT is_active WHERE id=$sid");
        set_flash('success', 'Status supplier diperbarui.');
    }
    header('Location: suppliers.php'); exit;
}

$suppliers = $conn->query("
    SELECT s.*, COUNT(p.id) AS purchase_count, COALESCE(SUM(p.total_amount),0) AS total_spent
    FROM suppliers s
    LEFT JOIN purchases p ON p.supplier_id = s.id AND p.status='completed'
    GROUP BY s.id ORDER BY s.name
")->fetch_all(MYSQLI_ASSOC);
$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-truck me-2" style="color:var(--rose)"></i>Tambah Supplier</span>
      </div>
      <div class="section-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="ais-label">Nama Supplier *</label>
            <input type="text" name="name" class="ais-input" placeholder="Nama perusahaan/toko" required>
          </div>
          <div class="mb-3">
            <label class="ais-label">No. Telepon</label>
            <input type="text" name="phone" class="ais-input" placeholder="021-xxxx / 08xx">
          </div>
          <div class="mb-4">
            <label class="ais-label">Alamat</label>
            <textarea name="address" class="ais-input" rows="3" placeholder="Alamat supplier..."></textarea>
          </div>
          <button type="submit" class="btn-rose w-100" style="padding:10px;">
            <i class="bi bi-plus me-2"></i>Tambah Supplier
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-truck me-2" style="color:var(--rose)"></i>Daftar Supplier (<?= count($suppliers) ?>)</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr><th>#</th><th>Nama</th><th>Telepon</th><th>Total PO</th><th>Total Belanja</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($suppliers as $i => $s): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></div>
                <?php if ($s['address']): ?>
                <div style="font-size:0.74rem;color:var(--muted);"><?= htmlspecialchars(substr($s['address'],0,50)) ?><?= strlen($s['address'])>50?'...':'' ?></div>
                <?php endif; ?>
              </td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
              <td><?= $s['purchase_count'] ?> PO</td>
              <td style="font-weight:500;color:var(--rose);">Rp <?= number_format($s['total_spent'],0,',','.') ?></td>
              <td>
                <?php if ($s['is_active']): ?>
                <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.72rem;">Aktif</span>
                <?php else: ?>
                <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:0.72rem;">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm me-1" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.72rem;"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="sid" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm" style="background:#f0f0f0;color:#555;border-radius:8px;font-size:0.72rem;">
                    <i class="bi bi-toggle-<?= $s['is_active']?'on text-success':'off text-danger' ?>"></i>
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
  <div class="modal-dialog"><form method="POST" class="modal-content">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="sid" id="edit_sid">
    <div class="modal-header" style="border-bottom:1px solid #ede4dd;">
      <h5 class="modal-title" style="font-family:'Playfair Display',serif;">Edit Supplier</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <div class="mb-3"><label class="ais-label">Nama *</label><input type="text" name="name" id="edit_name" class="ais-input" required></div>
      <div class="mb-3"><label class="ais-label">Telepon</label><input type="text" name="phone" id="edit_phone" class="ais-input"></div>
      <div class="mb-3"><label class="ais-label">Alamat</label><textarea name="address" id="edit_address" class="ais-input" rows="3"></textarea></div>
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

<?php $extra_js = <<<JS
<script>
function openEdit(s) {
    document.getElementById('edit_sid').value      = s.id;
    document.getElementById('edit_name').value     = s.name;
    document.getElementById('edit_phone').value    = s.phone || '';
    document.getElementById('edit_address').value  = s.address || '';
    document.getElementById('edit_is_active').value = s.is_active;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
