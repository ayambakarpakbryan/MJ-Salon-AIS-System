<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$page_title = 'Transaksi Baru';
$conn = db_connect();

$services_raw = $conn->query("SELECT * FROM services WHERE is_active=1 ORDER BY category, name");
$services_by_cat = [];
while ($s = $services_raw->fetch_assoc()) {
    $services_by_cat[$s['category']][] = $s;
}

$conn->close();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="section-card mb-3">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-person-circle me-2" style="color:var(--rose)"></i>Langkah 1 — Informasi Pelanggan</span>
      </div>
      <div class="section-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="ais-label">Nomor HP</label>
            <div class="d-flex gap-2">
              <input type="text" id="phone_search" class="ais-input" placeholder="08XXXXXXXXXX" maxlength="20">
              <button class="btn-rose" type="button" onclick="lookupCustomer()" style="white-space:nowrap;padding:10px 14px;"><i class="bi bi-search"></i></button>
            </div>
            <div id="customer_status" class="mt-2" style="font-size:0.78rem;"></div>
          </div>
          <div class="col-md-6">
            <label class="ais-label">Nama Pelanggan <span style="color:var(--rose)">*</span></label>
            <input type="text" id="customer_name" class="ais-input" placeholder="Nama lengkap" required>
          </div>
          <input type="hidden" id="customer_id" value="">
        </div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-scissors me-2" style="color:var(--rose)"></i>Langkah 2 — Pilih Layanan</span>
        <span id="dipilih_count" style="font-size:0.78rem;color:var(--muted);">0 dipilih</span>
      </div>
      <div class="section-card-body">
        <?php foreach ($services_by_cat as $cat => $items): ?>
        <div class="mb-3">
          <div style="font-size:0.72rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #ede4dd;">
            <?= htmlspecialchars($cat) ?>
          </div>
          <div class="row g-2">
            <?php foreach ($items as $svc): ?>
            <div class="col-sm-6">
              <div class="service-card"
                   data-id="<?= $svc['id'] ?>"
                   data-name="<?= htmlspecialchars($svc['name'], ENT_QUOTES) ?>"
                   data-price="<?= (int)$svc['price'] ?>"
                   onclick="toggleService(this)">
                <div class="d-flex justify-content-between align-items-center">
                  <span style="font-size:0.84rem;"><?= htmlspecialchars($svc['name']) ?></span>
                  <span style="font-size:0.82rem;font-weight:600;color:var(--rose);">Rp <?= number_format($svc['price'],0,',','.') ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="section-card" style="position:sticky;top:80px;">
      <div class="section-card-header">
        <span class="section-title"><i class="bi bi-receipt me-2" style="color:var(--rose)"></i>Ringkasan Pesanan</span>
      </div>
      <div class="section-card-body">
        <div id="order_list" style="min-height:60px;margin-bottom:16px;">
          <p style="color:var(--muted);font-size:0.83rem;text-align:center;padding:20px 0;margin:0;"><i class="bi bi-cart"></i><br>Belum ada layanan dipilih</p>
        </div>
        <div style="border-top:1.5px dashed #ede4dd;padding-top:14px;">
          <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;color:var(--muted);">
            <span>Subtotal</span><span id="subtotal_display">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:1.05rem;font-weight:600;margin-top:8px;">
            <span>Total</span><span id="total_display" style="color:var(--rose);">Rp 0</span>
          </div>
        </div>
        <div class="mt-3">
          <label class="ais-label">Metode Pembayaran</label>
          <div class="d-flex gap-2">
            <div class="payment-method-btn active" id="btn_cash" onclick="setPayMethod('cash')"><i class="bi bi-cash"></i> Cash</div>
            <div class="payment-method-btn" id="btn_ewallet" onclick="setPayMethod('ewallet')"><i class="bi bi-phone"></i> E-Wallet</div>
          </div>
        </div>
        <div id="cash_section" class="mt-3">
          <label class="ais-label">Jumlah Bayar (Rp)</label>
          <input type="number" id="amount_paid" class="ais-input" placeholder="0" min="0" step="1000" oninput="updateChange()">
          <div id="change_display" style="font-size:0.82rem;margin-top:6px;"></div>
        </div>
        <div class="mt-3">
          <label class="ais-label">Catatan (opsional)</label>
          <textarea id="txn_notes" class="ais-input" rows="2" placeholder="Instruksi khusus..."></textarea>
        </div>
        <button class="btn-rose w-100 mt-3" id="btn_proses" onclick="processTransaction()" style="padding:13px;font-size:0.95rem;">
          <i class="bi bi-check-circle me-2"></i>Proses Transaksi
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.service-card { border:1.5px solid #ede4dd; border-radius:10px; padding:10px 14px; cursor:pointer; display:block; transition:border-color .15s,background .15s; user-select:none; }
.service-card:hover  { border-color:var(--rose); background:#fdf8f2; }
.service-card.dipilih { border-color:var(--rose); background:#fce7ea; }
.payment-method-btn { flex:1; border:1.5px solid #ede4dd; border-radius:10px; padding:9px 14px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; font-size:0.85rem; transition:border-color .15s,background .15s; user-select:none; }
.payment-method-btn.active { border-color:var(--rose); background:#fce7ea; color:var(--rose); font-weight:500; }
.order-line { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid #f0e8e1; font-size:0.83rem; }
.order-line:last-child { border-bottom:none; }
</style>

<script>
var selectedServices = {};
var paymentMethod    = 'cash';

function toggleService(card) {
    var id    = card.getAttribute('data-id');
    var name  = card.getAttribute('data-name');
    var price = parseInt(card.getAttribute('data-price'), 10);

    if (selectedServices[id]) {
        delete selectedServices[id];
        card.classList.remove('dipilih');
    } else {
        selectedServices[id] = { name: name, price: price };
        card.classList.add('dipilih');
    }
    updateOrderSummary();
}

function updateOrderSummary() {
    var ids  = Object.keys(selectedServices);
    document.getElementById('dipilih_count').textContent = ids.length + ' dipilih';

    if (ids.length === 0) {
        document.getElementById('order_list').innerHTML = '<p style="color:var(--muted);font-size:0.83rem;text-align:center;padding:20px 0;margin:0;"><i class="bi bi-cart"></i><br>Belum ada layanan dipilih</p>';
        document.getElementById('subtotal_display').textContent = 'Rp 0';
        document.getElementById('total_display').textContent    = 'Rp 0';
        updateChange(); return;
    }

    var html = '', total = 0;
    for (var i = 0; i < ids.length; i++) {
        var s = selectedServices[ids[i]];
        total += s.price;
        html += '<div class="order-line"><span>' + s.name + '</span><span style="font-weight:500;color:var(--rose);">Rp ' + s.price.toLocaleString('id-ID') + '</span></div>';
    }
    document.getElementById('order_list').innerHTML = html;
    var fmt = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('subtotal_display').textContent = fmt;
    document.getElementById('total_display').textContent    = fmt;
    updateChange();
}

function setPayMethod(m) {
    paymentMethod = m;
    document.getElementById('btn_cash').classList.toggle('active',    m === 'cash');
    document.getElementById('btn_ewallet').classList.toggle('active', m === 'ewallet');
    document.getElementById('cash_section').style.display = m === 'cash' ? 'block' : 'none';
    updateChange();
}

function updateChange() {
    if (paymentMethod !== 'cash') { document.getElementById('change_display').textContent = ''; return; }
    var total = getTotalAmount();
    var paid  = parseInt(document.getElementById('amount_paid').value, 10) || 0;
    var el    = document.getElementById('change_display');
    if (paid > 0 && paid >= total) {
        el.innerHTML = '<span style="color:#166534;">Kembalian: Rp ' + (paid - total).toLocaleString('id-ID') + '</span>';
    } else if (paid > 0) {
        el.innerHTML = '<span style="color:#b91c1c;">Kurang: Rp ' + (total - paid).toLocaleString('id-ID') + ' lagi</span>';
    } else { el.textContent = ''; }
}

function getTotalAmount() {
    var ids = Object.keys(selectedServices), total = 0;
    for (var i = 0; i < ids.length; i++) total += selectedServices[ids[i]].price;
    return total;
}

function lookupCustomer() {
    var phone = document.getElementById('phone_search').value.trim();
    if (!phone) { alert('Masukkan nomor HP terlebih dahulu.'); return; }
    fetch('../process/lookup_customer.php?phone=' + encodeURIComponent(phone))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var el = document.getElementById('customer_status');
            if (d.found) {
                document.getElementById('customer_id').value   = d.id;
                document.getElementById('customer_name').value = d.name;
                el.innerHTML = '<span style="color:#166534;"><i class="bi bi-check-circle"></i> Pelanggan lama: ' + d.name + '</span>';
            } else {
                document.getElementById('customer_id').value   = '';
                document.getElementById('customer_name').value = '';
                el.innerHTML = '<span style="color:#b45309;"><i class="bi bi-info-circle"></i> Pelanggan baru — isi nama di bawah</span>';
            }
        }).catch(function() { alert('Gagal mencari pelanggan.'); });
}

function processTransaction() {
    var custName = document.getElementById('customer_name').value.trim();
    if (!custName) { alert('⚠ Nama pelanggan harus diisi.'); return; }

    var ids = Object.keys(selectedServices);
    if (ids.length === 0) { alert('⚠ Pilih minimal satu layanan.'); return; }

    var total = getTotalAmount();
    var paid  = parseInt(document.getElementById('amount_paid').value, 10) || 0;
    if (paymentMethod === 'cash' && paid < total) {
        alert('⚠ Jumlah bayar kurang! Total: Rp ' + total.toLocaleString('id-ID')); return;
    }

    var list = [];
    for (var i = 0; i < ids.length; i++) {
        list.push({ id: ids[i], name: selectedServices[ids[i]].name, price: selectedServices[ids[i]].price });
    }

    var btn = document.getElementById('btn_proses');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';

    fetch('../process/process_transaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            customer_id:    document.getElementById('customer_id').value,
            customer_name:  custName,
            customer_phone: document.getElementById('phone_search').value.trim(),
            services:       list,
            payment_method: paymentMethod,
            amount_paid:    paymentMethod === 'cash' ? paid : total,
            notes:          document.getElementById('txn_notes').value.trim()
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            window.location.href = '../staff/receipt.php?receipt=' + encodeURIComponent(res.receipt_no);
        } else {
            alert('Error: ' + res.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Proses Transaksi';
        }
    })
    .catch(function() {
        alert('Terjadi kesalahan jaringan.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Proses Transaksi';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>