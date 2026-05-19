<?php
// ============================================================
// MJ Salon AIS - Form Tambah Pembelian
// admin/purchase_form.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Tambah Pembelian';
$conn = db_connect();

$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$products  = $conn->query("SELECT * FROM products WHERE is_active=1 ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
$conn->close();

require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-3">
  <!-- Form -->
  <div class="col-lg-8">
    <div class="section-card mb-3">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-cart-plus me-2" style="color:var(--rose)"></i>Informasi Pembelian</span>
      </div>
      <div class="section-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="ais-label">Tanggal Pembelian <span style="color:var(--rose)">*</span></label>
            <input type="date" id="purchase_date" class="ais-input" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="ais-label">Supplier</label>
            <select id="supplier_id" class="ais-input">
              <option value="">-- Tanpa Supplier --</option>
              <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="ais-label">Metode Pembayaran <span style="color:var(--rose)">*</span></label>
            <select id="payment_method" class="ais-input">
              <option value="cash">Cash / Tunai</option>
              <option value="transfer">Transfer Bank</option>
              <option value="kredit">Kredit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="ais-label">Catatan</label>
            <input type="text" id="purchase_notes" class="ais-input" placeholder="Keterangan pembelian...">
          </div>
        </div>
      </div>
    </div>

    <!-- Item List -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-list-check me-2" style="color:var(--rose)"></i>Item Pembelian</span>
        <button type="button" class="btn-rose" onclick="addItem()" style="font-size:0.78rem;padding:7px 14px;">
          <i class="bi bi-plus me-1"></i>Tambah Item
        </button>
      </div>
      <div class="section-card-body">
        <div id="items_container">
          <!-- Item rows appended by JS -->
        </div>
        <p id="no_items_msg" style="color:var(--muted);font-size:0.83rem;text-align:center;padding:20px 0;">
          <i class="bi bi-box-seam" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
          Klik "Tambah Item" untuk menambahkan barang
        </p>
      </div>
    </div>
  </div>

  <!-- Summary -->
  <div class="col-lg-4">
    <div class="section-card" style="position:sticky;top:80px;">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-receipt me-2" style="color:var(--rose)"></i>Ringkasan</span>
      </div>
      <div class="section-card-body">
        <div id="summary_list" style="min-height:60px;margin-bottom:16px;">
          <p style="color:var(--muted);font-size:0.82rem;">Belum ada item.</p>
        </div>
        <div style="border-top:1.5px dashed #ede4dd;padding-top:14px;">
          <div class="d-flex justify-content-between" style="font-size:1.05rem;font-weight:700;color:var(--rose);">
            <span>Total</span>
            <span id="grand_total_display">Rp 0</span>
          </div>
        </div>

        <div class="info-badge mt-3" style="width:100%;border-radius:10px;">
          <i class="bi bi-journal-text"></i>
          <span style="font-size:0.75rem;">
            <strong>AIS:</strong> Sistem akan otomatis membuat jurnal akuntansi:<br>
            Debit: Biaya Bahan — Credit: Kas/Bank
          </span>
        </div>

        <button class="btn-rose w-100 mt-3" onclick="savePurchase()" style="padding:13px;font-size:0.95rem;">
          <i class="bi bi-check-circle me-2"></i>Simpan Pembelian
        </button>
        <a href="purchasing.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:10px;padding:10px;font-size:0.88rem;">
          Batal
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Product data for JS -->
<script>
const PRODUCTS = <?= json_encode($products) ?>;
let itemCount = 0;
let items = {};

function addItem() {
    document.getElementById('no_items_msg').style.display = 'none';
    const idx = ++itemCount;
    const id  = 'item_' + idx;
    items[id]  = {name:'', qty:1, unit:'pcs', price:0};

    const opts = PRODUCTS.map(p =>
        `<option value="${p.id}" data-name="${p.name}" data-unit="${p.unit}">${p.name} (${p.category})</option>`
    ).join('');

    const html = `
    <div id="row_${id}" style="background:#fdf8f2;border:1.5px solid #ede4dd;border-radius:10px;padding:14px;margin-bottom:10px;">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="ais-label">Nama Barang</label>
          <select class="ais-input" onchange="selectProduct(this, '${id}')">
            <option value="">-- Pilih Produk / Ketik Manual --</option>
            ${opts}
          </select>
          <input type="text" class="ais-input mt-1" id="name_${id}" placeholder="Atau ketik nama barang..."
                 oninput="items['${id}'].name=this.value;updateSummary()">
        </div>
        <div class="col-md-2">
          <label class="ais-label">Qty</label>
          <input type="number" class="ais-input" id="qty_${id}" value="1" min="1"
                 oninput="items['${id}'].qty=parseInt(this.value)||1;updateSummary()">
        </div>
        <div class="col-md-2">
          <label class="ais-label">Satuan</label>
          <input type="text" class="ais-input" id="unit_${id}" value="pcs" placeholder="pcs"
                 oninput="items['${id}'].unit=this.value">
        </div>
        <div class="col-md-2">
          <label class="ais-label">Harga Satuan</label>
          <input type="number" class="ais-input" id="price_${id}" value="0" min="0"
                 oninput="items['${id}'].price=parseFloat(this.value)||0;updateSummary()">
        </div>
        <div class="col-md-1 text-end">
          <label class="ais-label">&nbsp;</label>
          <button type="button" class="btn btn-sm"
                  style="background:#fee2e2;color:#b91c1c;border-radius:8px;width:100%;padding:9px 0;"
                  onclick="removeItem('${id}')">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>`;

    document.getElementById('items_container').insertAdjacentHTML('beforeend', html);
    updateSummary();
}

function selectProduct(sel, id) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('name_'+id).value = opt.dataset.name;
    document.getElementById('unit_'+id).value = opt.dataset.unit;
    items[id].name = opt.dataset.name;
    items[id].unit = opt.dataset.unit;
    items[id].product_id = opt.value;
    updateSummary();
}

function removeItem(id) {
    delete items[id];
    document.getElementById('row_'+id).remove();
    if (Object.keys(items).length === 0) {
        document.getElementById('no_items_msg').style.display = 'block';
    }
    updateSummary();
}

function updateSummary() {
    let html  = '';
    let total = 0;
    Object.entries(items).forEach(([id, item]) => {
        const sub = (item.qty || 0) * (item.price || 0);
        total += sub;
        html += `<div style="display:flex;justify-content:space-between;font-size:0.82rem;padding:5px 0;border-bottom:1px solid #f0e8e1;">
            <span>${item.name || '(belum diisi)'}</span>
            <span style="font-weight:500;color:var(--rose);">Rp ${sub.toLocaleString('id-ID')}</span>
        </div>`;
    });
    document.getElementById('summary_list').innerHTML = html || '<p style="color:var(--muted);font-size:0.82rem;">Belum ada item.</p>';
    document.getElementById('grand_total_display').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function savePurchase() {
    // Validasi
    const date   = document.getElementById('purchase_date').value;
    const method = document.getElementById('payment_method').value;
    const ids    = Object.keys(items);

    if (!date)          { showToast('Tanggal pembelian harus diisi.','error'); return; }
    if (ids.length===0) { showToast('Tambahkan minimal 1 item pembelian.','error'); return; }

    let valid = true;
    ids.forEach(id => {
        if (!items[id].name.trim()) { showToast('Nama barang belum diisi.','error'); valid=false; }
        if (!items[id].price || items[id].price<=0) { showToast('Harga satuan harus lebih dari 0.','error'); valid=false; }
    });
    if (!valid) return;

    // Build payload
    const itemList = ids.map(id => ({
        product_id: items[id].product_id || null,
        name:  document.getElementById('name_'+id).value.trim(),
        qty:   parseInt(document.getElementById('qty_'+id).value) || 1,
        unit:  document.getElementById('unit_'+id).value.trim() || 'pcs',
        price: parseFloat(document.getElementById('price_'+id).value) || 0,
    }));

    const payload = {
        purchase_date:  date,
        supplier_id:    document.getElementById('supplier_id').value || null,
        payment_method: method,
        notes:          document.getElementById('purchase_notes').value.trim(),
        items:          itemList,
    };

    showLoading();
    fetch('../process/process_purchase.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(res => {
        hideLoading();
        if (res.success) {
            showToast('Pembelian berhasil disimpan!','success');
            setTimeout(() => window.location.href='purchase_detail.php?id='+res.id, 1200);
        } else {
            showToast(res.message,'error');
        }
    })
    .catch(() => { hideLoading(); showToast('Terjadi kesalahan jaringan.','error'); });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
