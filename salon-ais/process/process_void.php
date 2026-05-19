<?php
// ============================================================
// MJ Salon AIS - Process Void Transaksi
// process/process_void.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

header('Content-Type: application/json');

$receipt_no = trim($_POST['receipt_no'] ?? '');
$reason     = trim($_POST['reason']     ?? 'Dibatalkan oleh admin');

if ($receipt_no === '') {
    echo json_encode(['success'=>false,'message'=>'Nomor receipt diperlukan.']); exit;
}

$conn = db_connect();
$conn->begin_transaction();

try {
    // Cari transaksi
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE receipt_no=? AND status='completed' LIMIT 1");
    $stmt->bind_param('s', $receipt_no);
    $stmt->execute();
    $txn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$txn) throw new Exception('Transaksi tidak ditemukan atau sudah dibatalkan.');

    // Tandai void
    $stmt = $conn->prepare("UPDATE transactions SET status='void', notes=CONCAT(IFNULL(notes,''),' | VOID: ',?) WHERE id=?");
    $stmt->bind_param('si', $reason, $txn['id']);
    $stmt->execute();
    $stmt->close();

    $amt        = (float)$txn['total_amount'];
    $date       = date('Y-m-d');
    $desc       = "REVERSAL Void #{$txn['id']} ({$receipt_no}): {$reason}";
    $tid        = (int)$txn['id'];
    $asset_code = $txn['payment_method'] === 'cash' ? '1100' : '1110';
    $asset_name = $txn['payment_method'] === 'cash' ? 'Kas Tunai' : 'E-Wallet Masuk';

    // Jurnal pembalik - DEBIT Revenue (balik pendapatan)
    // Row1: ref_id(i), date(s), debit_amt(d), desc(s)        = isds
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('transaction', ?, ?, '4100', 'Pendapatan Jasa (Reversal)', ?, 0, ?)
    ");
    $stmt->bind_param('isds', $tid, $date, $amt, $desc);
    $stmt->execute();
    $stmt->close();

    // Row2: ref_id(i), date(s), code(s), name(s), credit_amt(d), desc(s) = isssds
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('transaction', ?, ?, ?, ?, 0, ?, ?)
    ");
    $stmt->bind_param('isssds', $tid, $date, $asset_code, $asset_name, $amt, $desc);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn->close();
    echo json_encode(['success'=>true,'message'=>"Transaksi {$receipt_no} berhasil dibatalkan."]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
