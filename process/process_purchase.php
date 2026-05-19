<?php
// ============================================================
// MJ Salon AIS - Process Pembelian Barang
// process/process_purchase.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

header('Content-Type: application/json');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { echo json_encode(['success'=>false,'message'=>'Data tidak valid.']); exit; }

$purchase_date  = trim($data['purchase_date']  ?? '');
$supplier_id    = !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null;
$payment_method = $data['payment_method'] ?? 'cash';
$notes          = trim($data['notes'] ?? '');
$items          = $data['items'] ?? [];
$staff_id       = (int)$_SESSION['user_id'];

// Validasi
if (!$purchase_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchase_date)) {
    echo json_encode(['success'=>false,'message'=>'Tanggal tidak valid.']); exit;
}
if (!in_array($payment_method, ['cash','transfer','kredit'])) {
    echo json_encode(['success'=>false,'message'=>'Metode pembayaran tidak valid.']); exit;
}
if (empty($items)) {
    echo json_encode(['success'=>false,'message'=>'Item pembelian tidak boleh kosong.']); exit;
}

// Hitung total
$total = 0;
foreach ($items as $item) {
    $total += max(1,(int)$item['qty']) * max(0,(float)$item['price']);
}
if ($total <= 0) {
    echo json_encode(['success'=>false,'message'=>'Total pembelian harus lebih dari 0.']); exit;
}

$conn = db_connect();
$conn->begin_transaction();

try {
    $purchase_no = 'PUR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    // INSERT purchases header
    $stmt = $conn->prepare("
        INSERT INTO purchases (purchase_no, supplier_id, staff_id, purchase_date, total_amount, payment_method, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)
    ");
    $stmt->bind_param('siisdss', $purchase_no, $supplier_id, $staff_id, $purchase_date, $total, $payment_method, $notes);
    $stmt->execute();
    $purchase_id = (int)$conn->insert_id;
    $stmt->close();

    // INSERT items + update stok
    foreach ($items as $item) {
        $product_id = !empty($item['product_id']) ? (int)$item['product_id'] : null;
        $item_name  = trim($item['name']);
        $qty        = max(1, (int)$item['qty']);
        $unit       = trim($item['unit'] ?: 'pcs');
        $unit_price = (float)$item['price'];
        $subtotal   = $qty * $unit_price;

        $stmt = $conn->prepare("
            INSERT INTO purchase_items (purchase_id, product_id, item_name, qty, unit, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisisdd', $purchase_id, $product_id, $item_name, $qty, $unit, $unit_price, $subtotal);
        $stmt->execute();
        $stmt->close();

        if ($product_id) {
            $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $product_id");
        }
    }

    // Journal entries - split menjadi 2 INSERT terpisah
    $debit_desc = "Pembelian barang - $purchase_no";

    if ($payment_method === 'cash') {
        $credit_code = '1100'; $credit_name = 'Kas Tunai';
    } elseif ($payment_method === 'transfer') {
        $credit_code = '1120'; $credit_name = 'Bank / Transfer Keluar';
    } else {
        $credit_code = '2100'; $credit_name = 'Hutang Dagang';
    }

    // DEBIT: Biaya Bahan & Perlengkapan
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('purchase', ?, ?, '5200', 'Biaya Bahan & Perlengkapan', ?, 0, ?)
    ");
    $stmt->bind_param('isds', $purchase_id, $purchase_date, $total, $debit_desc);
    $stmt->execute();
    $stmt->close();

    // CREDIT: Kas / Bank / Hutang
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('purchase', ?, ?, ?, ?, 0, ?, ?)
    ");
    $stmt->bind_param('isssds', $purchase_id, $purchase_date, $credit_code, $credit_name, $total, $debit_desc);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn->close();
    echo json_encode(['success'=>true, 'id'=>$purchase_id, 'purchase_no'=>$purchase_no]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success'=>false, 'message'=>'Gagal: '.$e->getMessage()]);
}
