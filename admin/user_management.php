<?php
// ============================================================
// MJ Salon AIS - Kelola Pengguna (Admin only)
// admin/user_management.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Kelola Pengguna';
$conn = db_connect();

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $uid      = (int)($_POST['uid']    ?? 0);
    $name     = trim($_POST['name']    ?? '');
    $username = trim($_POST['username']?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';
    $is_active= (int)($_POST['is_active'] ?? 1);

    // ── ADD user ──────────────────────────────────────────────
    if ($action === 'add') {
        $password = trim($_POST['password'] ?? '');

        // Validation
        if (!$name || !$username || !$password) {
            set_flash('error', 'Name, username and password are required.');
            header('Location: user_management.php'); exit;
        }
        if (strlen($password) < 6) {
            set_flash('error', 'Password minimal 6 karakter.');
            header('Location: user_management.php'); exit;
        }

        // Check duplicate username
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $chk->bind_param('s', $username);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            set_flash('error', "Username \"$username\" is already taken.");
            header('Location: user_management.php'); exit;
        }
        $chk->close();

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $conn->prepare("INSERT INTO users (name,username,password,role,is_active) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssssi', $name, $username, $hashed, $role, $is_active);
        $stmt->execute();
        $stmt->close();
        set_flash('success', "User \"$name\" created successfully.");

    // ── EDIT user ─────────────────────────────────────────────
    } elseif ($action === 'edit' && $uid > 0) {
        // Prevent self-deactivation
        if ($uid === (int)$_SESSION['user_id'] && !$is_active) {
            set_flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            header('Location: user_management.php'); exit;
        }

        $new_pw = trim($_POST['new_password'] ?? '');
        if ($new_pw !== '') {
            if (strlen($new_pw) < 6) {
                set_flash('error', 'New password must be at least 6 characters.');
                header('Location: user_management.php'); exit;
            }
            $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
            $stmt   = $conn->prepare("UPDATE users SET name=?,username=?,role=?,is_active=?,password=? WHERE id=?");
            $stmt->bind_param('sssisi', $name, $username, $role, $is_active, $hashed, $uid);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?,username=?,role=?,is_active=? WHERE id=?");
            $stmt->bind_param('sssii', $name, $username, $role, $is_active, $uid);
        }
        $stmt->execute();
        $stmt->close();
        set_flash('success', "User \"$name\" updated.");

    // ── TOGGLE active ─────────────────────────────────────────
    } elseif ($action === 'toggle' && $uid > 0) {
        if ($uid === (int)$_SESSION['user_id']) {
            set_flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        } else {
            $conn->query("UPDATE users SET is_active = NOT is_active WHERE id=$uid");
            set_flash('success', 'Status pengguna diperbarui.');
        }
    }

    header('Location: user_management.php'); exit;
}

// ── Fetch all users ───────────────────────────────────────────
$users = $conn->query("
    SELECT u.*,
           COUNT(t.id) AS txn_count
    FROM users u
    LEFT JOIN transactions t ON t.staff_id = u.id
    GROUP BY u.id
    ORDER BY u.role, u.name
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-3">
  <!-- Add User Form -->
  <div class="col-lg-4">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-person-plus me-2" style="color:var(--rose)"></i>Tambah Pengguna Baru</span>
      </div>
      <div class="section-card-body">
        <form method="POST" action="" id="add-user-form">
          <input type="hidden" name="action" value="add">

          <div class="mb-3">
            <label class="ais-label">Nama Lengkap <span style="color:var(--rose)">*</span></label>
            <input type="text" name="name" class="ais-input" placeholder="e.g. Maria Santos" required>
          </div>
          <div class="mb-3">
            <label class="ais-label">Username <span style="color:var(--rose)">*</span></label>
            <input type="text" name="username" class="ais-input" placeholder="e.g. maria" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="ais-label">Password <span style="color:var(--rose)">*</span></label>
            <div style="position:relative;">
              <input type="password" name="password" id="add_pw" class="ais-input" placeholder="Min. 6 characters" required autocomplete="new-password" style="padding-right:44px;">
              <button type="button" onclick="togglePw('add_pw')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="add_pw_icon"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="ais-label">Role</label>
            <select name="role" class="ais-input">
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="ais-label">Status</label>
            <select name="is_active" class="ais-input">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <button type="submit" class="btn-rose w-100" style="padding:11px;">
            <i class="bi bi-person-plus me-2"></i>Buat Pengguna
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Users List -->
  <div class="col-lg-8">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title">
          <i class="bi bi-people me-2" style="color:var(--rose)"></i>
          Pengguna Sistem (<?= count($users) ?>)
        </span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr>
              <th>#</th><th>Name</th><th>Username</th>
              <th>Role</th><th>Transaksi</th>
              <th>Status</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:9px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:<?= $u['role']==='admin' ? 'var(--rose)' : '#5b9bd5' ?>;
                       color:#fff;display:flex;align-items:center;justify-content:center;font-weight:500;font-size:0.82rem;flex-shrink:0;">
                    <?= strtoupper(substr($u['name'],0,1)) ?>
                  </div>
                  <span style="font-weight:500;"><?= htmlspecialchars($u['name']) ?></span>
                  <?php if ($u['id'] == $_SESSION['user_id']): ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:0.65rem;padding:2px 7px;border-radius:20px;">You</span>
                  <?php endif; ?>
                </div>
              </td>
              <td><code style="font-size:0.8rem;"><?= htmlspecialchars($u['username']) ?></code></td>
              <td>
                <span style="background:<?= $u['role']==='admin' ? '#fce7ea' : '#dbeafe' ?>;
                             color:<?= $u['role']==='admin' ? 'var(--rose)' : '#1e40af' ?>;
                             padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:500;">
                  <?= ucfirst($u['role']) ?>
                </span>
              </td>
              <td style="text-align:center;">
                <span style="font-size:0.82rem;color:var(--muted);"><?= $u['txn_count'] ?></span>
              </td>
              <td>
                <?php if ($u['is_active']): ?>
                  <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:20px;font-size:0.75rem;">Active</span>
                <?php else: ?>
                  <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:0.75rem;">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm me-1" style="background:#f5ede8;color:var(--rose);border-radius:8px;font-size:0.75rem;"
                        onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                        title="Edit user">
                  <i class="bi bi-pencil"></i>
                </button>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="uid"    value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm"
                          style="background:#f0f0f0;color:#555;border-radius:8px;font-size:0.75rem;"
                          title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                          data-confirm="<?= $u['is_active'] ? 'Deactivate this user?' : 'Activate this user?' ?>">
                    <i class="bi bi-toggle-<?= $u['is_active'] ? 'on text-success' : 'off text-danger' ?>"></i>
                  </button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Internal Control Note -->
    <div class="info-badge mt-3" style="width:100%;border-radius:10px;">
      <i class="bi bi-shield-check"></i>
      <span>
        <strong>Internal Control:</strong> Role-based access enforced system-wide.
        Admin users can view reports and manage data. Staff can only process transactions.
        Inactive accounts cannot log in.
      </span>
    </div>
  </div>
</div>

<!-- Edit Pengguna Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="uid" id="edit_uid">
      <div class="modal-header" style="border-bottom:1px solid #ede4dd;">
        <h5 class="modal-title" style="font-family:'Playfair Display',serif;">Edit Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <div class="mb-3">
          <label class="ais-label">Nama Lengkap</label>
          <input type="text" name="name" id="edit_name" class="ais-input" required>
        </div>
        <div class="mb-3">
          <label class="ais-label">Username</label>
          <input type="text" name="username" id="edit_username" class="ais-input" required>
        </div>
        <div class="mb-3">
          <label class="ais-label">Password Baru <span style="color:var(--muted);font-weight:300;">(kosongkan untuk tidak mengubah)</span></label>
          <div style="position:relative;">
            <input type="password" name="new_password" id="edit_pw" class="ais-input"
                   placeholder="Min. 6 characters" autocomplete="new-password" style="padding-right:44px;">
            <button type="button" onclick="togglePw('edit_pw')"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
              <i class="bi bi-eye" id="edit_pw_icon"></i>
            </button>
          </div>
        </div>
        <div class="mb-3">
          <label class="ais-label">Role</label>
          <select name="role" id="edit_role" class="ais-input">
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="ais-label">Status</label>
          <select name="is_active" id="edit_is_active" class="ais-input">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #ede4dd;gap:10px;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn-rose" style="padding:9px 24px;">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php $extra_js = <<<JS
<script>
function openEditModal(u) {
    document.getElementById('edit_uid').value       = u.id;
    document.getElementById('edit_name').value      = u.name;
    document.getElementById('edit_username').value  = u.username;
    document.getElementById('edit_role').value      = u.role;
    document.getElementById('edit_is_active').value = u.is_active;
    document.getElementById('edit_pw').value        = '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
function togglePw(inputId) {
    const input  = document.getElementById(inputId);
    const icon   = document.getElementById(inputId + '_icon');
    const isText = input.type === 'text';
    input.type   = isText ? 'password' : 'text';
    icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
