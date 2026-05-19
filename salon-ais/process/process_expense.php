<?php
// ============================================================
// MJ Salon AIS - Process Pengeluaran
// process/process_expense.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

header('Content-Type: application/json');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { echo json_encode(['success'=>false,'message'=>'Data tidak valid.']); exit; }

$expense_date   = trim($data['expense_date']   ?? '');
$category       = trim($data['category']       ?? '');
$description    = trim($data['description']    ?? '');
$total_amount   = (float)($data['total_amount'] ?? 0);
$payment_method = $data['payment_method']       ?? 'cash';
$notes          = trim($data['notes']           ?? '');
$staff_id       = (int)$_SESSION['user_id'];

// Validasi
$valid_cats = ['Gaji','Utilitas','Marketing','Perawatan','Lain-lain'];
if (!$expense_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expense_date)) {
    echo json_encode(['success'=>false,'message'=>'Tanggal tidak valid.']); exit;
}
if (!in_array($category, $valid_cats)) {
    echo json_encode(['success'=>false,'message'=>'Kategori tidak valid.']); exit;
}
if (empty($description)) {
    echo json_encode(['success'=>false,'message'=>'Deskripsi harus diisi.']); exit;
}
if ($total_amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Jumlah harus lebih dari 0.']); exit;
}
if (!in_array($payment_method, ['cash','transfer'])) {
    echo json_encode(['success'=>false,'message'=>'Metode tidak valid.']); exit;
}

// Map category → kode akun debit
$debit_map = [
    'Gaji'      => ['5100','Biaya Gaji Karyawan'],
    'Utilitas'  => ['5300','Biaya Utilitas'],
    'Marketing' => ['5700','Biaya Marketing'],
    'Perawatan' => ['5500','Biaya Perawatan'],
    'Lain-lain' => ['5600','Biaya Lain-lain'],
];
[$debit_code, $debit_name] = $debit_map[$category];
$credit_code = ($payment_method === 'cash') ? '1100' : '1120';
$credit_name = ($payment_method === 'cash') ? 'Kas Tunai' : 'Bank / Transfer Keluar';

$conn = db_connect();
$conn->begin_transaction();

try {
    $expense_no = 'EXP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    // INSERT expense
    $stmt = $conn->prepare("
        INSERT INTO expenses
            (expense_no, staff_id, expense_date, category, description, total_amount, payment_method, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?)
    ");
    $stmt->bind_param('sisssdss',
        $expense_no, $staff_id, $expense_date,
        $category, $description, $total_amount,
        $payment_method, $notes
    );
    $stmt->execute();
    $expense_id = (int)$conn->insert_id;
    $stmt->close();

    // INSERT journal DEBIT
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('expense', ?, ?, ?, ?, ?, 0, ?)
    ");
    $stmt->bind_param('isssds', $expense_id, $expense_date, $debit_code, $debit_name, $total_amount, $description);
    $stmt->execute();
    $stmt->close();

    // INSERT journal CREDIT
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('expense', ?, ?, ?, ?, 0, ?, ?)
    ");
    $stmt->bind_param('isssds', $expense_id, $expense_date, $credit_code, $credit_name, $total_amount, $description);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn->close();
    echo json_encode(['success'=>true, 'id'=>$expense_id, 'expense_no'=>$expense_no]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success'=>false, 'message'=>'Gagal: '.$e->getMessage()]);
}
