<?php
// ============================================================
// MJ Salon AIS - Entry Point
// index.php
//
// Smart redirect:
//  - Not logged in?    → login.php
//  - Logged in, admin? → admin/dashboard.php
//  - Logged in, staff? → staff/transaction.php
// ============================================================
require_once __DIR__ . '/auth/auth.php';

if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: staff/transaction.php');
    }
    exit;
}

header('Location: login.php');
exit;
