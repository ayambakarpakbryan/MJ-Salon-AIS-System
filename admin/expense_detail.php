<?php
// ============================================================
// MJ Salon AIS - Detail Pengeluaran
// admin/expense_detail.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: expenses.php'); exit; }

$conn = db_connect();
$stmt = $conn->prepare("
    SELECT e.*, u.name AS staff_name
    FROM expenses e JOIN users u ON u.id = e.staff_id
    WHERE e.id = ? LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$exp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exp) { header('Location: expenses.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM journals WHERE ref_type='expense' AND ref_id=? ORDER BY id");
$stmt->bind_param('i', $id);
$stmt->execute();
$journals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$page_title = 'Detail Pengeluaran — ' . $exp['expense_no'];
require_once __DIR__ . '/../includes/navbar.php';

$cat_colors = [
    'Gaji'=>'#fce7ea:var(--rose)','Utilitas'=>'#dbeafe:#1e40af',
    'Sewa'=>'#fef3c7:#92400e','Marketing'=>'#dcfce7:#166534',
    'Perawatan'=>'#f3e8ff:#7e22ce','Lain-lain'=>'#f1f5f9:#475569',
];
$cc = $cat_colors[$exp['category']] ?? '#f1f5f9:#475569';
[$bg,$color] = explode(':', $cc);
?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <a href="expenses.php" style="color:var(--muted);text-decoration:none;font-size:0.85rem;">
    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pengeluaran
  </a>
  <button onclick="window.print()" class="btn-gold ms-auto" style="padding:8px 18px;font-size:0.85rem;">
    <i class="bi bi-printer me-1"></i>Cetak
  </button>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <!-- Info Pengeluaran -->
    <div class="section-card mb-3">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-cash-coin me-2" style="color:var(--rose)"></i>Informasi Pengeluaran</span>
        <span class="<?= $exp['status']==='completed'?'badge-completed':'badge-void' ?>">
          <?= $exp['status']==='completed'?'Selesai':'Dibatalkan' ?>
        </span>
      </div>
      <div class="section-card-body">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="ais-label">No. Pengeluaran</div>
            <code style="font-size:0.95rem;color:#1a1218;font-weight:600;"><?= htmlspecialchars($exp['expense_no']) ?></code>
          </div>
          <div class="col-sm-6">
            <div class="ais-label">Tanggal</div>
            <div style="font-weight:500;"><?= date('d F Y', strtotime($exp['expense_date'])) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="ais-label">Kategori</div>
            <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:500;">
              <?= htmlspecialchars($exp['category']) ?>
            </span>
          </div>
          <div class="col-sm-6">
            <div class="ais-label">Metode Pembayaran</div>
            <span class="<?= $exp['payment_method']==='cash'?'badge-cash':'badge-ewallet' ?>">
              <?= $exp['payment_method']==='cash'?'Cash / Tunai':'Transfer Bank' ?>
            </span>
          </div>
          <div class="col-12">
            <div class="ais-label">Deskripsi</div>
            <div style="font-size:0.9rem;font-weight:500;"><?= htmlspecialchars($exp['description']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="ais-label">Dicatat Oleh</div>
            <div style="font-weight:500;"><?= htmlspecialchars($exp['staff_name']) ?></div>
          </div>
          <?php if ($exp['notes']): ?>
          <div class="col-12">
            <div class="ais-label">Catatan</div>
            <div style="font-size:0.85rem;background:#fdf8f2;padding:8px 12px;border-radius:8px;"><?= htmlspecialchars($exp['notes']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Journal Entries -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-journal-text me-2" style="color:var(--rose)"></i>Jurnal Akuntansi (Auto-Generated)</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr>
              <th>Tanggal</th><th>Kode Akun</th><th>Nama Akun</th>
              <th style="text-align:right;">Debit (Rp)</th>
              <th style="text-align:right;">Kredit (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($journals as $j): ?>
            <tr>
              <td><?= htmlspecialchars($j['journal_date']) ?></td>
              <td><span style="font-family:monospace;font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($j['account_code']) ?></span></td>
              <td><?= htmlspecialchars($j['account_name']) ?></td>
              <td style="text-align:right;color:#166534;font-weight:500;">
                <?= $j['debit']>0 ? 'Rp '.number_format($j['debit'],0,',','.') : '—' ?>
              </td>
              <td style="text-align:right;color:#b91c1c;font-weight:500;">
                <?= $j['credit']>0 ? 'Rp '.number_format($j['credit'],0,',','.') : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="padding:10px 14px;font-size:0.75rem;color:var(--muted);background:#fdf8f2;border-top:1px solid #ede4dd;">
        <i class="bi bi-info-circle me-1"></i>
        Jurnal dibuat otomatis saat pengeluaran disimpan. Debit = akun biaya meningkat. Kredit = kas/bank berkurang.
      </div>
    </div>
  </div>

  <!-- Summary -->
  <div class="col-lg-4">
    <div class="section-card" style="position:sticky;top:80px;">
      <div class="section-card-header">
        <span class="section-title">Ringkasan</span>
      </div>
      <div class="section-card-body">
        <div style="text-align:center;padding:20px 0;">
          <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;">Total Pengeluaran</div>
          <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--rose);margin-top:8px;line-height:1.1;">
            Rp <?= number_format($exp['total_amount'],0,',','.') ?>
          </div>
        </div>
        <div style="border-top:1px dashed #ede4dd;padding-top:14px;">
          <div class="d-flex justify-content-between mb-2" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Kategori</span>
            <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:2px 10px;border-radius:20px;font-size:0.75rem;"><?= htmlspecialchars($exp['category']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Pembayaran</span>
            <span class="<?= $exp['payment_method']==='cash'?'badge-cash':'badge-ewallet' ?>">
              <?= $exp['payment_method']==='cash'?'Cash':'Transfer' ?>
            </span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Tanggal</span>
            <span style="font-weight:500;"><?= date('d/m/Y', strtotime($exp['expense_date'])) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  .sidebar,.topbar,.btn-gold,.btn-rose { display:none!important; }
  .main-content { margin-left:0!important; }
}
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
