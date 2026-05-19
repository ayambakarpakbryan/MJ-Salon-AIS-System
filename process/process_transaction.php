<?php
// ============================================================
// MJ Salon AIS - Process Transaction
// process/process_transaction.php
//
// AIS Flowchart:
// [Receive JSON] → [Validate Session] → [Validate Inputs]
//   → [Customer Exists? Yes: use ID, No: create new]
//   → [Generate Receipt No]
//   → [INSERT transactions]
//   → [INSERT transaction_details (loop)]
//   → [Generate Journal Entries (debit cash / credit revenue)]
//   → [COMMIT or ROLLBACK on error]
//   → [Return JSON {success, receipt_no}]
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json');

// ── Read and decode JSON body ──────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

// ── INTERNAL CONTROL: Server-side validation ──────────────────
$customer_name  = trim($data['customer_name']  ?? '');
$customer_phone = trim($data['customer_phone'] ?? '');
$services       = $data['services']            ?? [];
$payment_method = $data['payment_method']      ?? 'cash';
$amount_paid    = (float)($data['amount_paid'] ?? 0);
$notes          = trim($data['notes']          ?? '');
$staff_id       = (int)$_SESSION['user_id'];

// Validate customer name
if ($customer_name === '') {
    echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
    exit;
}

// Validate services list
if (empty($services)) {
    echo json_encode(['success' => false, 'message' => 'No services selected.']);
    exit;
}

// Validate payment method
if (!in_array($payment_method, ['cash', 'ewallet'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']);
    exit;
}

// ── Calculate total amount ─────────────────────────────────────
$subtotal = 0;
foreach ($services as $s) {
    $subtotal += (float)$s['price'];
}
$total_amount  = $subtotal;  // No discount/tax for simplicity
$change_amount = max(0, $amount_paid - $total_amount);

// INTERNAL CONTROL: Payment validation
if ($payment_method === 'cash' && $amount_paid < $total_amount) {
    echo json_encode(['success' => false, 'message' => 'Amount paid is less than the total.']);
    exit;
}

// ── Database Operations ───────────────────────────────────────
$conn = db_connect();
$conn->begin_transaction();

try {
    // ── STEP 1: Resolve customer (existing or new) ─────────────
    $customer_id = (int)($data['customer_id'] ?? 0);

    if ($customer_id > 0) {
        // Verify customer still exists
        $chk = $conn->prepare("SELECT id FROM customers WHERE id=? LIMIT 1");
        $chk->bind_param('i', $customer_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $customer_id = 0; // Reset; will create new
        }
        $chk->close();
    }

    if ($customer_id === 0) {
        // Create new customer
        $stmt = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
        $stmt->bind_param('ss', $customer_name, $customer_phone);
        $stmt->execute();
        $customer_id = (int)$conn->insert_id;
        $stmt->close();
    }

    // ── STEP 2: Generate unique receipt number ─────────────────
    $receipt_no = 'MJS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    // ── STEP 3: Insert transaction header ──────────────────────
    $txn_date = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO transactions
            (receipt_no, customer_id, staff_id, transaction_date,
             subtotal, discount, total_amount, payment_method,
             amount_paid, change_amount, status, notes)
        VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 'completed', ?)
    ");
    // s=receipt_no i=customer_id i=staff_id s=txn_date
    // d=subtotal d=total_amount s=payment_method d=amount_paid d=change_amount s=notes
    $stmt->bind_param('siisddsdds',
        $receipt_no, $customer_id, $staff_id, $txn_date,
        $subtotal, $total_amount, $payment_method,
        $amount_paid, $change_amount, $notes
    );
    $stmt->execute();
    $transaction_id = (int)$conn->insert_id;
    $stmt->close();

    // ── STEP 4: Insert transaction details (line items) ────────
    foreach ($services as $s) {
        $svc_id    = (int)$s['id'];
        $svc_name  = trim($s['name']);
        $unit_price = (float)$s['price'];
        $qty        = 1;
        $line_sub   = $unit_price * $qty;

        $stmt = $conn->prepare("
            INSERT INTO transaction_details
                (transaction_id, service_id, service_name, qty, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisidd', $transaction_id, $svc_id, $svc_name, $qty, $unit_price, $line_sub);
        $stmt->execute();
        $stmt->close();
    }

    // ── STEP 5: Generate Accounting Journal Entries ────────────
    // Double-Entry:
    //   DEBIT  Cash / E-Wallet Receivable  (Asset increases)
    //   CREDIT Service Revenue             (Revenue increases)
    require_once __DIR__ . '/generate_journal.php';
    generate_journal($conn, $transaction_id, $txn_date, $total_amount, $payment_method);

    // ── COMMIT ─────────────────────────────────────────────────
    $conn->commit();
    $conn->close();

    echo json_encode(['success' => true, 'receipt_no' => $receipt_no]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
