<?php
// ============================================================
// MJ Salon AIS - Form Tambah Pengeluaran
// admin/expense_form.php
// ============================================================
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
require_admin();

$page_title = 'Tambah Pengeluaran';
require_once __DIR__ . '/../includes/navbar.php';

$categories = ['Gaji','Utilitas','Marketing','Perawatan','Lain-lain'];
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-title">
          <i class="bi bi-cash-coin me-2" style="color:var(--rose)"></i>Form Pengeluaran Operasional
        </span>
      </div>
      <div class="section-card-body">

        <div class="mb-3">
          <label class="ais-label">Tanggal <span style="color:var(--rose)">*</span></label>
          <input type="date" id="exp_date" class="ais-input" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="mb-3">
          <label class="ais-label">Kategori <span style="color:var(--rose)">*</span></label>
          <select id="exp_category" class="ais-input" onchange="updateAccountCode()">
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>"><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="ais-label">Deskripsi <span style="color:var(--rose)">*</span></label>
          <input type="text" id="exp_desc" class="ais-input" placeholder="Contoh: Tagihan Listrik Mei 2025, Gaji Karyawan Sarah...">
        </div>

        <div class="mb-3">
          <label class="ais-label">Jumlah (Rp) <span style="color:var(--rose)">*</span></label>
          <input type="number" id="exp_amount" class="ais-input" placeholder="0" min="1000" step="1000"
                 oninput="updatePreview()">
        </div>

        <div class="mb-3">
          <label class="ais-label">Metode Pembayaran <span style="color:var(--rose)">*</span></label>
          <div class="d-flex gap-2">
            <label class="payment-method-btn active" id="btn_cash" onclick="setPayMethod('cash')">
              <i class="bi bi-cash"></i> Cash / Tunai
            </label>
            <label class="payment-method-btn" id="btn_transfer" onclick="setPayMethod('transfer')">
              <i class="bi bi-bank"></i> Transfer Bank
            </label>
          </div>
          <input type="hidden" id="exp_method" value="cash">
        </div>

        <div class="mb-4">
          <label class="ais-label">Catatan (opsional)</label>
          <textarea id="exp_notes" class="ais-input" rows="2" placeholder="Keterangan tambahan..."></textarea>
        </div>

        <!-- AIS Journal Preview -->
        <div style="background:#f5f0ff;border:1px solid #d8b4fe;border-radius:10px;padding:14px;margin-bottom:20px;">
          <div style="font-size:0.72rem;font-weight:600;color:#7e22ce;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">
            <i class="bi bi-journal-text me-1"></i>Preview Jurnal Otomatis
          </div>
          <table style="width:100%;font-size:0.78rem;">
            <thead>
              <tr style="color:#aaa;font-size:0.68rem;text-transform:uppercase;">
                <th style="padding:4px 0;">Kode</th>
                <th>Akun</th>
                <th style="text-align:right;">Debit</th>
                <th style="text-align:right;">Kredit</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="padding:5px 0;"><span id="prev_debit_code" style="font-family:monospace;color:#555;">5100</span></td>
                <td><span id="prev_debit_name" style="color:#1a1218;">Biaya Gaji Karyawan</span></td>
                <td style="text-align:right;color:#166534;font-weight:600;" id="prev_debit_amt">Rp 0</td>
                <td style="text-align:right;">—</td>
              </tr>
              <tr>
                <td style="padding:5px 0;"><span id="prev_credit_code" style="font-family:monospace;color:#555;">1120</span></td>
                <td><span id="prev_credit_name" style="color:#1a1218;">Bank / Transfer Keluar</span></td>
                <td style="text-align:right;">—</td>
                <td style="text-align:right;color:#b91c1c;font-weight:600;" id="prev_credit_amt">Rp 0</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2">
          <button class="btn-rose flex-grow-1" onclick="saveExpense()" style="padding:12px;">
            <i class="bi bi-check-circle me-2"></i>Simpan Pengeluaran
          </button>
          <a href="expenses.php" class="btn btn-outline-secondary" style="border-radius:10px;padding:12px 20px;font-size:0.88rem;">
            Batal
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
.payment-method-btn {
  flex:1;border:1.5px solid #ede4dd;border-radius:10px;padding:10px 14px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  gap:6px;font-size:0.85rem;transition:border-color .15s,background .15s;
}
.payment-method-btn.active { border-color:var(--rose);background:#fce7ea;color:var(--rose);font-weight:500; }
</style>

<?php $extra_js = <<<JS
<script>
let payMethod = 'cash';

const ACCOUNT_MAP = {
    'Gaji':      {code:'5100', name:'Biaya Gaji Karyawan'},
    'Utilitas':  {code:'5300', name:'Biaya Utilitas'},
    'Marketing': {code:'5700', name:'Biaya Marketing'},
    'Perawatan': {code:'5500', name:'Biaya Perawatan'},
    'Lain-lain': {code:'5600', name:'Biaya Lain-lain'},
};

function setPayMethod(m) {
    payMethod = m;
    document.getElementById('exp_method').value = m;
    document.getElementById('btn_cash').classList.toggle('active', m==='cash');
    document.getElementById('btn_transfer').classList.toggle('active', m==='transfer');
    updatePreview();
}

function updateAccountCode() {
    updatePreview();
}

function updatePreview() {
    const cat    = document.getElementById('exp_category').value;
    const amount = parseFloat(document.getElementById('exp_amount').value) || 0;
    const acct   = ACCOUNT_MAP[cat] || {code:'5600', name:'Biaya Lain-lain'};
    const credit = payMethod === 'cash'
        ? {code:'1100', name:'Kas Tunai'}
        : {code:'1120', name:'Bank / Transfer Keluar'};

    document.getElementById('prev_debit_code').textContent  = acct.code;
    document.getElementById('prev_debit_name').textContent  = acct.name;
    document.getElementById('prev_credit_code').textContent = credit.code;
    document.getElementById('prev_credit_name').textContent = credit.name;
    const fmt = 'Rp ' + amount.toLocaleString('id-ID');
    document.getElementById('prev_debit_amt').textContent   = fmt;
    document.getElementById('prev_credit_amt').textContent  = fmt;
}

function saveExpense() {
    const date   = document.getElementById('exp_date').value;
    const cat    = document.getElementById('exp_category').value;
    const desc   = document.getElementById('exp_desc').value.trim();
    const amount = parseFloat(document.getElementById('exp_amount').value) || 0;
    const method = document.getElementById('exp_method').value;
    const notes  = document.getElementById('exp_notes').value.trim();

    if (!date)         { showToast('Tanggal harus diisi.','error'); return; }
    if (!desc)         { showToast('Deskripsi harus diisi.','error'); return; }
    if (amount <= 0)   { showToast('Jumlah harus lebih dari 0.','error'); return; }

    showLoading();
    fetch('../process/process_expense.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({expense_date:date, category:cat, description:desc, total_amount:amount, payment_method:method, notes:notes}),
    })
    .then(r => r.json())
    .then(res => {
        hideLoading();
        if (res.success) {
            showToast('Pengeluaran berhasil disimpan!','success');
            setTimeout(() => window.location.href='expense_detail.php?id='+res.id, 1200);
        } else {
            showToast(res.message,'error');
        }
    })
    .catch(() => { hideLoading(); showToast('Terjadi kesalahan jaringan.','error'); });
}

// init
updatePreview();
</script>
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
