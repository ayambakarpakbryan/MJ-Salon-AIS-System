<?php
// ============================================================
// MJ Salon AIS - AJAX: Customer Lookup
// process/lookup_customer.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');

// Validate input
if ($phone === '') {
    echo json_encode(['found' => false]);
    exit;
}

$conn = db_connect();
$stmt = $conn->prepare("SELECT id, name, phone, email FROM customers WHERE phone = ? LIMIT 1");
$stmt->bind_param('s', $phone);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($customer) {
    echo json_encode([
        'found' => true,
        'id'    => $customer['id'],
        'name'  => $customer['name'],
        'phone' => $customer['phone'],
    ]);
} else {
    echo json_encode(['found' => false]);
}
