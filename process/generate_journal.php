<?php
// ============================================================
// MJ Salon AIS - Journal Entry Generator (Updated)
// process/generate_journal.php
//
// Updated schema: journals sekarang pakai ref_type + ref_id
// (bukan transaction_id) supaya bisa cover purchase & expense
//
// Chart of Accounts:
//   1100 = Kas Tunai
//   1110 = E-Wallet Masuk
//   1120 = Bank / Transfer Keluar
//   2100 = Hutang Dagang
//   4100 = Pendapatan Jasa
//   5100 = Biaya Gaji Karyawan
//   5200 = Biaya Bahan & Perlengkapan
//   5300 = Biaya Utilitas
//   5400 = Biaya Sewa
//   5500 = Biaya Perawatan
//   5600 = Biaya Lain-lain
//   5700 = Biaya Marketing
// ============================================================

/**
 * Buat jurnal double-entry untuk transaksi jasa salon.
 * Debit  : Kas Tunai / E-Wallet (aset naik)
 * Kredit : Pendapatan Jasa (pendapatan naik)
 *
 * @param mysqli $conn
 * @param int    $txn_id
 * @param string $date        YYYY-MM-DD
 * @param float  $amount
 * @param string $pay_method  'cash' | 'ewallet'
 */
function generate_journal(mysqli $conn, int $txn_id, string $date, float $amount, string $pay_method): void
{
    if ($pay_method === 'cash') {
        $debit_code = '1100';
        $debit_name = 'Kas Tunai';
    } else {
        $debit_code = '1110';
        $debit_name = 'E-Wallet Masuk';
    }

    $desc = "Pendapatan jasa dari Transaksi #$txn_id";

    // DEBIT — aset bertambah
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('transaction', ?, ?, ?, ?, ?, 0, ?)
    ");
    $stmt->bind_param('isssds', $txn_id, $date, $debit_code, $debit_name, $amount, $desc);
    $stmt->execute();
    $stmt->close();

    // KREDIT — pendapatan bertambah
    $credit_code = '4100';
    $credit_name = 'Pendapatan Jasa';
    $stmt = $conn->prepare("
        INSERT INTO journals (ref_type, ref_id, journal_date, account_code, account_name, debit, credit, description)
        VALUES ('transaction', ?, ?, ?, ?, 0, ?, ?)
    ");
    $stmt->bind_param('isssds', $txn_id, $date, $credit_code, $credit_name, $amount, $desc);
    $stmt->execute();
    $stmt->close();
}
