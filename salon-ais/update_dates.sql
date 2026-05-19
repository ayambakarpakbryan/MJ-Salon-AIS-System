-- ============================================================
-- MJ Salon AIS - UPDATE SCRIPT
-- update_dates.sql
--
-- Jalankan file ini di phpMyAdmin → SQL
-- untuk mengupdate semua tanggal data sample
-- dari 2025 ke tanggal relative dari hari ini.
--
-- CARA PAKAI:
-- 1. Buka phpMyAdmin → pilih database mj_salon_ais
-- 2. Klik tab "SQL"
-- 3. Copy-paste seluruh isi file ini
-- 4. Klik "Go"
-- ============================================================

USE mj_salon_ais;

-- ============================================================
-- UPDATE tanggal transaksi (90 hari terakhir)
-- ============================================================
UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 89 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 89 DAY)
WHERE receipt_no = 'MJS-20250201-001';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 89 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 89 DAY)
WHERE receipt_no = 'MJS-20250201-002';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 88 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 88 DAY)
WHERE receipt_no = 'MJS-20250202-003';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 87 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 87 DAY)
WHERE receipt_no = 'MJS-20250203-004';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 86 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 86 DAY)
WHERE receipt_no = 'MJS-20250204-005';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 85 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 85 DAY)
WHERE receipt_no = 'MJS-20250205-006';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 84 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 84 DAY)
WHERE receipt_no = 'MJS-20250206-007';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 83 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 83 DAY)
WHERE receipt_no = 'MJS-20250207-008';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 82 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 82 DAY)
WHERE receipt_no = 'MJS-20250208-009';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 80 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 80 DAY)
WHERE receipt_no = 'MJS-20250210-010';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 79 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 79 DAY)
WHERE receipt_no = 'MJS-20250211-011';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 78 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 78 DAY)
WHERE receipt_no = 'MJS-20250212-012';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 77 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 77 DAY)
WHERE receipt_no = 'MJS-20250213-013';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 76 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 76 DAY)
WHERE receipt_no = 'MJS-20250214-014';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 75 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 75 DAY)
WHERE receipt_no = 'MJS-20250215-015';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 73 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 73 DAY)
WHERE receipt_no = 'MJS-20250217-016';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 72 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 72 DAY)
WHERE receipt_no = 'MJS-20250218-017';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 71 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 71 DAY)
WHERE receipt_no = 'MJS-20250219-018';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 70 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 70 DAY)
WHERE receipt_no = 'MJS-20250220-019';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 69 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 69 DAY)
WHERE receipt_no = 'MJS-20250221-020';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 68 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 68 DAY)
WHERE receipt_no = 'MJS-20250222-021';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 66 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 66 DAY)
WHERE receipt_no = 'MJS-20250224-022';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 65 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 65 DAY)
WHERE receipt_no = 'MJS-20250225-023';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 64 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 64 DAY)
WHERE receipt_no = 'MJS-20250226-024';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 63 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 63 DAY)
WHERE receipt_no = 'MJS-20250227-025';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 62 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 62 DAY)
WHERE receipt_no = 'MJS-20250228-026';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 61 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 61 DAY)
WHERE receipt_no = 'MJS-20250301-027';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 59 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 59 DAY)
WHERE receipt_no = 'MJS-20250303-028';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 58 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 58 DAY)
WHERE receipt_no = 'MJS-20250304-029';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 57 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 57 DAY)
WHERE receipt_no = 'MJS-20250305-030';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 56 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 56 DAY)
WHERE receipt_no = 'MJS-20250306-031';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 55 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 55 DAY)
WHERE receipt_no = 'MJS-20250307-032';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 54 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 54 DAY)
WHERE receipt_no = 'MJS-20250308-033';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 52 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 52 DAY)
WHERE receipt_no = 'MJS-20250310-034';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 51 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 51 DAY)
WHERE receipt_no = 'MJS-20250311-035';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 50 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 50 DAY)
WHERE receipt_no = 'MJS-20250312-036';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 49 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 49 DAY)
WHERE receipt_no = 'MJS-20250313-037';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 48 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 48 DAY)
WHERE receipt_no = 'MJS-20250314-038';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 47 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 47 DAY)
WHERE receipt_no = 'MJS-20250315-039';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 45 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 45 DAY)
WHERE receipt_no = 'MJS-20250317-040';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 44 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 44 DAY)
WHERE receipt_no = 'MJS-20250318-041';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 43 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 43 DAY)
WHERE receipt_no = 'MJS-20250319-042';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 42 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 42 DAY)
WHERE receipt_no = 'MJS-20250320-043';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 41 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 41 DAY)
WHERE receipt_no = 'MJS-20250321-044';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 40 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 40 DAY)
WHERE receipt_no = 'MJS-20250322-045';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 38 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 38 DAY)
WHERE receipt_no = 'MJS-20250324-046';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 37 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 37 DAY)
WHERE receipt_no = 'MJS-20250325-047';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 36 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 36 DAY)
WHERE receipt_no = 'MJS-20250326-048';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 35 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 35 DAY)
WHERE receipt_no = 'MJS-20250327-049';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 34 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 34 DAY)
WHERE receipt_no = 'MJS-20250328-050';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 33 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 33 DAY)
WHERE receipt_no = 'MJS-20250329-051';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 31 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 31 DAY)
WHERE receipt_no = 'MJS-20250331-052';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 30 DAY)
WHERE receipt_no = 'MJS-20250401-053';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 29 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 29 DAY)
WHERE receipt_no = 'MJS-20250402-054';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 28 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 28 DAY)
WHERE receipt_no = 'MJS-20250403-055';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 27 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 27 DAY)
WHERE receipt_no = 'MJS-20250404-056';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 26 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 26 DAY)
WHERE receipt_no = 'MJS-20250405-057';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 24 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 24 DAY)
WHERE receipt_no = 'MJS-20250407-058';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 23 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 23 DAY)
WHERE receipt_no = 'MJS-20250408-059';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 22 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 22 DAY)
WHERE receipt_no = 'MJS-20250409-060';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 21 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 21 DAY)
WHERE receipt_no = 'MJS-20250410-061';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 20 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 20 DAY)
WHERE receipt_no = 'MJS-20250411-062';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 19 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 19 DAY)
WHERE receipt_no = 'MJS-20250412-063';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 17 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 17 DAY)
WHERE receipt_no = 'MJS-20250414-064';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 16 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 16 DAY)
WHERE receipt_no = 'MJS-20250415-065';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 15 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 15 DAY)
WHERE receipt_no = 'MJS-20250416-066';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 14 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 14 DAY)
WHERE receipt_no = 'MJS-20250417-067';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 13 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 13 DAY)
WHERE receipt_no = 'MJS-20250418-068';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 12 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 12 DAY)
WHERE receipt_no = 'MJS-20250419-069';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 10 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 10 DAY)
WHERE receipt_no = 'MJS-20250421-070';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 9 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 9 DAY)
WHERE receipt_no = 'MJS-20250422-071';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 8 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 8 DAY)
WHERE receipt_no = 'MJS-20250423-072';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 7 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 7 DAY)
WHERE receipt_no = 'MJS-20250424-073';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 6 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 6 DAY)
WHERE receipt_no = 'MJS-20250425-074';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 5 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 5 DAY)
WHERE receipt_no = 'MJS-20250426-075';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 3 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 3 DAY)
WHERE receipt_no = 'MJS-20250428-076';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 2 DAY)
WHERE receipt_no = 'MJS-20250429-077';

UPDATE transactions SET
    transaction_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    created_at       = DATE_SUB(NOW(),     INTERVAL 1 DAY)
WHERE receipt_no = 'MJS-20250430-078';

-- ============================================================
-- UPDATE journal dates untuk transaksi (ikuti transaction_date)
-- ============================================================
UPDATE journals j
JOIN transactions t ON j.ref_type = 'transaction' AND j.ref_id = t.id
SET j.journal_date = t.transaction_date;

-- ============================================================
-- UPDATE tanggal pembelian barang
-- ============================================================
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 89 DAY) WHERE purchase_no = 'PUR-20250201-001';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 80 DAY) WHERE purchase_no = 'PUR-20250210-002';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 72 DAY) WHERE purchase_no = 'PUR-20250218-003';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 61 DAY) WHERE purchase_no = 'PUR-20250301-004';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 52 DAY) WHERE purchase_no = 'PUR-20250310-005';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 42 DAY) WHERE purchase_no = 'PUR-20250320-006';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY) WHERE purchase_no = 'PUR-20250401-007';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL 16 DAY) WHERE purchase_no = 'PUR-20250415-008';
UPDATE purchases SET purchase_date = DATE_SUB(CURDATE(), INTERVAL  6 DAY) WHERE purchase_no = 'PUR-20250425-009';

-- ============================================================
-- UPDATE tanggal pengeluaran
-- ============================================================
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 89 DAY) WHERE expense_no = 'EXP-20250201-001';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 85 DAY) WHERE expense_no = 'EXP-20250205-002';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 83 DAY) WHERE expense_no = 'EXP-20250207-003';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 80 DAY) WHERE expense_no = 'EXP-20250210-004';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 80 DAY) WHERE expense_no = 'EXP-20250210-005';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 80 DAY) WHERE expense_no = 'EXP-20250210-006';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 75 DAY) WHERE expense_no = 'EXP-20250215-007';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 70 DAY) WHERE expense_no = 'EXP-20250220-008';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 65 DAY) WHERE expense_no = 'EXP-20250225-009';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 61 DAY) WHERE expense_no = 'EXP-20250301-010';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 57 DAY) WHERE expense_no = 'EXP-20250305-011';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 55 DAY) WHERE expense_no = 'EXP-20250307-012';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 52 DAY) WHERE expense_no = 'EXP-20250310-013';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 52 DAY) WHERE expense_no = 'EXP-20250310-014';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 52 DAY) WHERE expense_no = 'EXP-20250310-015';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 47 DAY) WHERE expense_no = 'EXP-20250315-016';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 42 DAY) WHERE expense_no = 'EXP-20250320-017';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 37 DAY) WHERE expense_no = 'EXP-20250325-018';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY) WHERE expense_no = 'EXP-20250401-019';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 26 DAY) WHERE expense_no = 'EXP-20250405-020';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 24 DAY) WHERE expense_no = 'EXP-20250407-021';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 21 DAY) WHERE expense_no = 'EXP-20250410-022';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 21 DAY) WHERE expense_no = 'EXP-20250410-023';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 21 DAY) WHERE expense_no = 'EXP-20250410-024';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 16 DAY) WHERE expense_no = 'EXP-20250415-025';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 13 DAY) WHERE expense_no = 'EXP-20250418-026';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL 11 DAY) WHERE expense_no = 'EXP-20250420-027';
UPDATE expenses SET expense_date = DATE_SUB(CURDATE(), INTERVAL  6 DAY) WHERE expense_no = 'EXP-20250425-028';

-- ============================================================
-- UPDATE journal dates untuk purchases & expenses
-- ============================================================
UPDATE journals j
JOIN purchases p ON j.ref_type = 'purchase' AND j.ref_id = p.id
SET j.journal_date = p.purchase_date;

UPDATE journals j
JOIN expenses e ON j.ref_type = 'expense' AND j.ref_id = e.id
SET j.journal_date = e.expense_date;

-- ============================================================
-- VERIFIKASI - cek berapa banyak data yang terupdate
-- ============================================================
SELECT 'Transaksi bulan ini' AS info,
       COUNT(*) AS jumlah,
       FORMAT(SUM(total_amount), 0) AS total_rupiah
FROM transactions
WHERE MONTH(transaction_date) = MONTH(CURDATE())
  AND YEAR(transaction_date)  = YEAR(CURDATE())
  AND status = 'completed'

UNION ALL

SELECT 'Pengeluaran bulan ini',
       COUNT(*),
       FORMAT(SUM(total_amount), 0)
FROM expenses
WHERE MONTH(expense_date) = MONTH(CURDATE())
  AND YEAR(expense_date)  = YEAR(CURDATE())
  AND status = 'completed'

UNION ALL

SELECT 'Semua transaksi total',
       COUNT(*),
       FORMAT(SUM(total_amount), 0)
FROM transactions
WHERE status = 'completed';
