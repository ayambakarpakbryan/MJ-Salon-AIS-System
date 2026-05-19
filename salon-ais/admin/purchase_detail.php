<?php
// ============================================================
// MJ Salon AIS - Detail Pembelian
// admin/purchase_detail.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: purchasing.php'); exit; }

$conn = db_connect();

$stmt = $conn->prepare("
    SELECT p.*, COALESCE(s.name,'(Tanpa Supplier)') AS supplier_name,
           s.phone AS supplier_phone, u.name AS staff_name
    FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    JOIN users u ON u.id = p.staff_id
    WHERE p.id = ? LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$purchase) { header('Location: purchasing.php'); exit; }

// Items
$stmt = $conn->prepare("SELECT pi.*, pr.category FROM purchase_items pi LEFT JOIN products pr ON pr.id=pi.product_id WHERE pi.purchase_id=? ORDER BY pi.id");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Journals
$stmt = $conn->prepare("SELECT * FROM journals WHERE ref_type='purchase' AND ref_id=? ORDER BY id");
$stmt->bind_param('i', $id);
$stmt->execute();
$journals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
$page_title = 'Detail Pembelian — ' . $purchase['purchase_no'];
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <a href="purchasing.php" style="color:var(--muted);text-decoration:none;font-size:0.85rem;">
    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Pembelian
  </a>
  <button onclick="window.print()" class="btn-gold ms-auto" style="padding:8px 18px;font-size:0.85rem;">
    <i class="bi bi-printer me-1"></i>Cetak
  </button>
</div>

<div class="row g-3">
  <!-- Left: Detail -->
  <div class="col-lg-8">
    <!-- Header Info -->
    <div class="section-card mb-3">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-cart3 me-2" style="color:var(--rose)"></i>Informasi Pembelian</span>
        <span class="<?= $purchase['status']==='completed' ? 'badge-completed' : 'badge-void' ?>">
          <?= $purchase['status']==='completed' ? 'Selesai' : 'Dibatalkan' ?>
        </span>
      </div>
      <div class="section-card-body">
        <div class="row g-3">
          <div class="col-sm-6">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">No. Purchase Order</div>
            <code style="font-size:1rem;color:#1a1218;font-weight:600;"><?= htmlspecialchars($purchase['purchase_no']) ?></code>
          </div>
          <div class="col-sm-6">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Tanggal</div>
            <div style="font-weight:500;"><?= date('d F Y', strtotime($purchase['purchase_date'])) ?></div>
          </div>
          <div class="col-sm-6">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Supplier</div>
            <div style="font-weight:500;"><?= htmlspecialchars($purchase['supplier_name']) ?></div>
            <?php if ($purchase['supplier_phone']): ?>
            <div style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($purchase['supplier_phone']) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-sm-6">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Metode Pembayaran</div>
            <?php
            $mc = ['cash'=>'badge-cash','transfer'=>'badge-ewallet','kredit'=>'badge-refunded'];
            $ml = ['cash'=>'Cash / Tunai','transfer'=>'Transfer Bank','kredit'=>'Kredit'];
            ?>
            <span class="<?= $mc[$purchase['payment_method']] ?>">
              <?= $ml[$purchase['payment_method']] ?>
            </span>
          </div>
          <div class="col-sm-6">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Dicatat Oleh</div>
            <div style="font-weight:500;"><?= htmlspecialchars($purchase['staff_name']) ?></div>
          </div>
          <?php if ($purchase['notes']): ?>
          <div class="col-12">
            <div style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Catatan</div>
            <div style="font-size:0.85rem;background:#fdf8f2;padding:8px 12px;border-radius:8px;"><?= htmlspecialchars($purchase['notes']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="section-card mb-3">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-list-ul me-2" style="color:var(--rose)"></i>Daftar Item (<?= count($items) ?> barang)</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="ais-table">
          <thead>
            <tr><th>#</th><th>Nama Barang</th><th>Kategori</th><th>Qty</th><th>Satuan</th><th>Harga Satuan</th><th>Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
              <td style="font-weight:500;"><?= htmlspecialchars($item['item_name']) ?></td>
              <td style="font-size:0.8rem;color:var(--muted);"><?= htmlspecialchars($item['category'] ?? '-') ?></td>
              <td><?= $item['qty'] ?></td>
              <td><?= htmlspecialchars($item['unit']) ?></td>
              <td>Rp <?= number_format($item['unit_price'],0,',','.') ?></td>
              <td style="font-weight:600;">Rp <?= number_format($item['subtotal'],0,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:#fdf8f2;">
              <td colspan="6" style="font-weight:700;padding:12px 14px;">TOTAL</td>
              <td style="font-weight:700;color:var(--rose);padding:12px 14px;">
                Rp <?= number_format($purchase['total_amount'],0,',','.') ?>
              </td>
            </tr>
          </tfoot>
        </table>
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
        Jurnal dibuat otomatis oleh sistem AIS saat pembelian disimpan.
        Debit = akun biaya meningkat. Kredit = akun kas/bank berkurang.
      </div>
    </div>
  </div>

  <!-- Right: Summary Card -->
  <div class="col-lg-4">
    <div class="section-card" style="position:sticky;top:80px;">
      <div class="section-card-header">
        <span class="section-title">Ringkasan PO</span>
      </div>
      <div class="section-card-body">
        <div style="text-align:center;padding:20px 0;">
          <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;">Total Pembelian</div>
          <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--rose);margin-top:8px;">
            Rp <?= number_format($purchase['total_amount'],0,',','.') ?>
          </div>
        </div>
        <div style="border-top:1px dashed #ede4dd;padding-top:14px;">
          <div class="d-flex justify-content-between mb-2" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Jumlah Item</span>
            <span style="font-weight:500;"><?= count($items) ?> barang</span>
          </div>
          <div class="d-flex justify-content-between mb-2" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Pembayaran</span>
            <span class="<?= $mc[$purchase['payment_method']] ?>"><?= $ml[$purchase['payment_method']] ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:0.83rem;">
            <span style="color:var(--muted);">Status</span>
            <span class="<?= $purchase['status']==='completed' ? 'badge-completed' : 'badge-void' ?>">
              <?= $purchase['status']==='completed' ? 'Selesai' : 'Dibatalkan' ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  .sidebar, .topbar, .btn-gold, .btn-rose, .nav-item-link { display:none!important; }
  .main-content { margin-left:0!important; }
}
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
