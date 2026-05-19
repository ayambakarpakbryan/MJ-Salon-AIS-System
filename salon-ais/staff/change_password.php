<?php
// ============================================================
// MJ Salon AIS - Ganti Password (Staff)
// staff/change_password.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$page_title = 'Ganti Password';
$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = trim($_POST['current_password'] ?? '');
    $new_pw   = trim($_POST['new_password']     ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current, $row['password'])) {
        set_flash('error', 'Current password is incorrect.');
    } elseif (strlen($new_pw) < 6) {
        set_flash('error', 'New password must be at least 6 characters.');
    } elseif ($new_pw !== $confirm) {
        set_flash('error', 'Passwords do not match.');
    } elseif ($new_pw === $current) {
        set_flash('error', 'New password cannot be the same as your current password.');
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $upd    = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->bind_param('si', $hashed, $_SESSION['user_id']);
        $upd->execute();
        $upd->close();
        set_flash('success', 'Password changed successfully.');
    }

    header('Location: change_password.php'); exit;
}
$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title">
          <i class="bi bi-shield-lock me-2" style="color:var(--rose)"></i>Ganti Password
        </span>
      </div>
      <div class="section-card-body">
        <form method="POST" action="">
          <div class="mb-3">
            <label class="ais-label">Password Saat Ini <span style="color:var(--rose)">*</span></label>
            <div style="position:relative;">
              <input type="password" name="current_password" id="pw_current" class="ais-input"
                     placeholder="Masukkan password saat ini" required style="padding-right:44px;">
              <button type="button" onclick="togglePw('pw_current','ic_current')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="ic_current"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="ais-label">Password Baru <span style="color:var(--rose)">*</span></label>
            <div style="position:relative;">
              <input type="password" name="new_password" id="pw_new" class="ais-input"
                     placeholder="Min. 6 karakter" required style="padding-right:44px;">
              <button type="button" onclick="togglePw('pw_new','ic_new')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="ic_new"></i>
              </button>
            </div>
            <div style="margin-top:8px;">
              <div style="background:#f0e8e1;border-radius:20px;height:5px;overflow:hidden;">
                <div id="pw_strength_bar" style="height:100%;border-radius:20px;transition:width .3s,background .3s;width:0%;background:#ccc;"></div>
              </div>
              <div id="pw_strength_label" style="font-size:0.72rem;color:var(--muted);margin-top:4px;"></div>
            </div>
          </div>
          <div class="mb-4">
            <label class="ais-label">Confirm Password Baru <span style="color:var(--rose)">*</span></label>
            <div style="position:relative;">
              <input type="password" name="confirm_password" id="pw_confirm" class="ais-input"
                     placeholder="Ulangi password baru" required style="padding-right:44px;">
              <button type="button" onclick="togglePw('pw_confirm','ic_confirm')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="ic_confirm"></i>
              </button>
            </div>
            <div id="pw_match_label" style="font-size:0.72rem;margin-top:4px;"></div>
          </div>
          <button type="submit" class="btn-rose w-100" style="padding:12px;">
            <i class="bi bi-check-circle me-2"></i>Perbarui Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php $extra_js = <<<JS
<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'text' ? 'password' : 'text';
    icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
document.getElementById('pw_new').addEventListener('input', function () {
    const val = this.value, bar = document.getElementById('pw_strength_bar'), lbl = document.getElementById('pw_strength_label');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        {w:'0%',bg:'#ccc',label:''},
        {w:'25%',bg:'#ef4444',label:'Weak'},
        {w:'50%',bg:'#f59e0b',label:'Fair'},
        {w:'75%',bg:'#3b82f6',label:'Good'},
        {w:'90%',bg:'#22c55e',label:'Strong'},
        {w:'100%',bg:'#16a34a',label:'Very Strong'},
    ];
    const lvl = levels[Math.min(score,5)];
    bar.style.width = lvl.w; bar.style.background = lvl.bg;
    lbl.textContent = lvl.label; lbl.style.color = lvl.bg;
    checkMatch();
});
document.getElementById('pw_confirm').addEventListener('input', checkMatch);
function checkMatch() {
    const pw1 = document.getElementById('pw_new').value;
    const pw2 = document.getElementById('pw_confirm').value;
    const lbl = document.getElementById('pw_match_label');
    if (!pw2) { lbl.textContent=''; return; }
    lbl.innerHTML = pw1===pw2
        ? '<span style="color:#166534;"><i class="bi bi-check-circle"></i> Password cocok</span>'
        : '<span style="color:#b91c1c;"><i class="bi bi-x-circle"></i> Tidak cocok</span>';
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
