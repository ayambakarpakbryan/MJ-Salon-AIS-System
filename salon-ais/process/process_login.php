<?php
// ============================================================
// MJ Salon AIS - Process Login
// process/process_login.php
//
// AIS Internal Control Flowchart:
//   [Start] → [Receive Input] → [Validate Fields?]
//     No  → Error: empty fields
//     Yes → [User Exists?]
//       No  → Error: invalid credentials
//       Yes → [Is Active?]
//         No  → Error: account disabled
//         Yes → [Password Match?]
//           No  → Error: wrong password
//           Yes → [Set Session] → [Role = Admin?]
//             Yes → /admin/dashboard.php
//             No  → /staff/transaction.php
// ============================================================

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}

// ── STEP 1: Retrieve and sanitize inputs ─────────────────────
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// ── STEP 2: Internal Control — validate that fields are not empty ─
if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password harus diisi.';
    header('Location: ../login.php'); exit;
}

// ── STEP 3: Query the database for user ───────────────────────
$conn  = db_connect();
$stmt  = $conn->prepare("SELECT id, name, username, password, role, is_active FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();
$conn->close();

// ── STEP 4: Internal Control — does user exist? ───────────────
if (!$user) {
    $_SESSION['login_error'] = 'Username atau password salah.';
    header('Location: ../login.php'); exit;
}

// ── STEP 5: Internal Control — is account active? ────────────
if ((int)$user['is_active'] !== 1) {
    $_SESSION['login_error'] = 'Akun Anda dinonaktifkan. Hubungi admin.';
    header('Location: ../login.php'); exit;
}

// ── STEP 6: Internal Control — verify password ───────────────
if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Username atau password salah.';
    header('Location: ../login.php'); exit;
}

// ── STEP 7: All checks passed — create session ───────────────
session_regenerate_id(true);  // Prevent session fixation attack
$_SESSION['user_id']  = $user['id'];
$_SESSION['name']     = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

// ── STEP 8: Role-based redirect ───────────────────────────────
if ($user['role'] === 'admin') {
    header('Location: ../admin/dashboard.php'); exit;
} else {
    header('Location: ../staff/transaction.php'); exit;
}
