<?php
// ============================================================
// MJ Salon AIS - Buku Jurnal Umum (Updated)
// admin/journals.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Jurnal Umum (Ledger)';
$conn = db_connect();

$date_from  = $_GET['from']     ?? date('Y-m-01');
$date_to    = $_GET['to']       ?? date('Y-m-d');
$ref_filter = $_GET['ref_type'] ?? '';

$error = '';
if ($date_from > $date_to) $error = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';

$entries       = [];
$total_debit   = 0;
$total_credit  = 0;

if (!$error) {
    $where  = "WHERE j.journal_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    $types  = 'ss';

    if (in_array($ref_filter, ['transaction','purchase','expense'])) {
        $where  .= " AND j.ref_type = ?";
        $params[] = $ref_filter;
        $types   .= 's';
    }

    $stmt = $conn->prepare("
        SELECT j.*,
            CASE j.ref_type
                WHEN 'transaction' THEN t.receipt_no
                WHEN 'purchase'    THEN p.purchase_no
                WHEN 'expense'     THEN e.expense_no
            END AS ref_no
        FROM journals j
        LEFT JOIN transactions t ON j.ref_type='transaction' AND t.id=j.ref_id
        LEFT JOIN purchases    p ON j.ref_type='purchase'    AND p.id=j.ref_id
        LEFT JOIN expenses     e ON j.ref_type='expense'     AND e.id=j.ref_id
        $where
        ORDER BY j.journal_date, j.ref_type, j.ref_id, j.id
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($entries as $e) {
        $total_debit  += (float)$e['debit'];
        $total_credit += (float)$e['credit'];
    }
}

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';

$ref_labels  = ['transaction'=>'Pendapatan Jasa','purchase'=>'Pembelian','expense'=>'Pengeluaran'];
$ref_colors  = ['transaction'=>'badge-completed','purchase'=>'badge-ewallet','expense'=>'badge-refunded'];
?>

<!-- Filter -->
<div class="section-card mb-4">
  <div class="section-card-header">
    <span class="section-title"><i class="bi bi-journal-text me-2" style="color:var(--rose)"></i>Filter Jurnal Umum</span>
  </div>
  <div class="section-card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-sm-3">
        <label class="ais-label">Dari Tanggal</label>
        <input type="date" name="from" class="ais-input" value="<?= htmlspecialchars($date_from) ?>">
      </div>
      <div class="col-sm-3">
        <label class="ais-label">Sampai Tanggal</label>
        <input type="date" name="to" class="ais-input" value="<?= htmlspecialchars($date_to) ?>">
      </div>
      <div class="col-sm-3">
        <label class="ais-label">Tipe Transaksi</label>
        <select name="ref_type" class="ais-input">
          <option value="">Semua</option>
          <option value="transaction" <?= $ref_filter==='transaction'?'selected':'' ?>>Pendapatan Jasa</option>
          <option value="purchase"    <?= $ref_filter==='purchase'?'selected':'' ?>>Pembelian Barang</option>
          <option value="expense"     <?= $ref_filter==='expense'?'selected':'' ?>>Pengeluaran Operasional</option>
        </select>
      </div>
      <div class="col-sm-3">
        <button type="submit" class="btn-rose w-100" style="padding:10px;">
          <i class="bi bi-search me-1"></i>Tampilkan Jurnal
        </button>
      </div>
    </form>
    <?php if ($error): ?>
    <div class="flash-error mt-3"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- AIS Concept Banner -->
<div style="background:#fdf3e3;border:1px solid #f5d89b;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:0.82rem;color:#7a5a00;">
  <i class="bi bi-lightbulb-fill me-2"></i>
  <strong>Konsep AIS:</strong> Setiap transaksi keuangan (pendapatan, pembelian, pengeluaran) otomatis menghasilkan
  jurnal <strong>double-entry</strong>. Total Debit harus selalu sama dengan total Kredit (prinsip akuntansi).
  <span style="display:inline-block;margin-left:8px;padding:2px 8px;border-radius:20px;font-size:0.72rem;"
        style="background:<?= round($total_debit,0)===round($total_credit,0)?'#dcfce7;color:#166534':'#fee2e2;color:#b91c1c' ?>">
  </span>
</div>

<!-- KPI Totals -->
<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dcfce7;color:#166534;"><i class="bi bi-arrow-down-circle"></i></div>
      <div class="stat-value" style="font-size:1.4rem;">Rp <?= number_format($total_debit,0,',','.') ?></div>
      <div class="stat-label">Total Debit</div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-arrow-up-circle"></i></div>
      <div class="stat-value" style="font-size:1.4rem;">Rp <?= number_format($total_credit,0,',','.') ?></div>
      <div class="stat-label">Total Kredit</div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card">
      <?php $balanced = round($total_debit,0) === round($total_credit,0); ?>
      <div class="stat-icon" style="background:<?= $balanced?'#dcfce7;color:#166534':'#fee2e2;color:#b91c1c' ?>;"><i class="bi bi-<?= $balanced?'check':'x' ?>-circle"></i></div>
      <div class="stat-value" style="font-size:1.2rem;color:<?= $balanced?'#166534':'#b91c1c' ?>;">
        <?= $balanced ? 'BALANCE' : 'TIDAK BALANCE' ?>
      </div>
      <div class="stat-label">Status Neraca Jurnal</div>
    </div>
  </div>
</div>

<!-- Journal Table -->
<div class="section-card">
  <div class="section-card-header">
    <span class="section-title">Entri Jurnal — <?= count($entries) ?> baris</span>
    <div style="font-size:0.78rem;color:var(--muted);">
      <?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="ais-table">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Tipe</th>
          <th>No. Referensi</th>
          <th>Kode Akun</th>
          <th>Nama Akun</th>
          <th>Keterangan</th>
          <th style="text-align:right;">Debit (Rp)</th>
          <th style="text-align:right;">Kredit (Rp)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($entries)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">Tidak ada entri jurnal untuk periode ini.</td></tr>
        <?php endif; ?>
        <?php
        $prev_group = null;
        foreach ($entries as $e):
            $group_key = $e['ref_type'] . '_' . $e['ref_id'];
            $is_new    = ($group_key !== $prev_group);
            $prev_group = $group_key;
        ?>
        <tr style="<?= $is_new && $e !== $entries[0] ? 'border-top:2px solid #f0e8e1;' : '' ?>">
          <td style="font-size:0.8rem;"><?= date('d/m/Y', strtotime($e['journal_date'])) ?></td>
          <td>
            <span class="<?= $ref_colors[$e['ref_type']] ?? 'badge-completed' ?>" style="font-size:0.7rem;">
              <?= $ref_labels[$e['ref_type']] ?? $e['ref_type'] ?>
            </span>
          </td>
          <td><code style="font-size:0.72rem;"><?= htmlspecialchars($e['ref_no'] ?? '-') ?></code></td>
          <td><span style="font-family:monospace;font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($e['account_code']) ?></span></td>
          <td style="font-size:0.83rem;font-weight:<?= $e['debit']>0?'500':'400' ?>;"><?= htmlspecialchars($e['account_name']) ?></td>
          <td style="font-size:0.75rem;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($e['description'] ?? '') ?>
          </td>
          <td style="text-align:right;color:#166534;font-weight:500;">
            <?= $e['debit']>0 ? 'Rp '.number_format($e['debit'],0,',','.') : '—' ?>
          </td>
          <td style="text-align:right;color:#b91c1c;font-weight:500;">
            <?= $e['credit']>0 ? 'Rp '.number_format($e['credit'],0,',','.') : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if (!empty($entries)): ?>
      <tfoot>
        <tr style="background:#fdf8f2;font-weight:700;">
          <td colspan="6" style="padding:12px 14px;">TOTAL</td>
          <td style="text-align:right;color:#166534;padding:12px 14px;">Rp <?= number_format($total_debit,0,',','.') ?></td>
          <td style="text-align:right;color:#b91c1c;padding:12px 14px;">Rp <?= number_format($total_credit,0,',','.') ?></td>
        </tr>
        <?php if (!$balanced): ?>
        <tr style="background:#fef2f2;">
          <td colspan="8" style="text-align:center;color:#b91c1c;padding:10px;font-size:0.82rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Selisih: Rp <?= number_format(abs($total_debit - $total_credit),0,',','.') ?> — Jurnal tidak balance!
          </td>
        </tr>
        <?php endif; ?>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
