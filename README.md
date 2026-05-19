# MJ Salon Information System
### Accounting Information System (AIS) — PHP Native / MySQL / Bootstrap 5

---

## 📋 Project Overview

MJ Salon AIS is a complete web-based Accounting Information System built for a real salon business. It is **not** just a cashier app — every transaction automatically generates double-entry accounting journal entries, maintains a general ledger, and produces professional financial reports. The system implements full AIS concepts including internal controls, role-based authorization, and audit trails.

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x Native (no framework) |
| Database | MySQL 5.7+ / MariaDB 10.x |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| Fonts | Google Fonts (Playfair Display, DM Sans) |
| Charts | Chart.js 4.x |
| PDF | Browser Print-to-PDF (Dompdf optional) |
| Server | Laragon or XAMPP |

---

## ⚡ Quick Setup (5 Steps)

### Step 1 — Copy project files
Place the `salon-ais` folder inside your web root:
- **Laragon:** `C:\laragon\www\salon-ais\`
- **XAMPP:** `C:\xampp\htdocs\salon-ais\`

### Step 2 — Start your server
- Laragon: Click **Start All**
- XAMPP: Start **Apache** and **MySQL**

### Step 3 — Run the web installer
Open your browser and go to:
```
http://localhost/salon-ais/setup.php
```
Click **Run Installer**. The installer will:
- Create the `mj_salon_ais` database
- Create all 6 tables
- Seed demo users, services, and customers

### Step 4 — Delete setup.php
After successful installation, delete `setup.php` from the project folder for security.

### Step 5 — Log in!
```
http://localhost/salon-ais/
```

---

## 🔑 Demo Login Accounts

| Username | Password | Role | Access |
|----------|----------|------|--------|
| `admin`  | `password` | Admin | Dashboard, Reports, Journals, Transactions, Services, Users |
| `sarah`  | `password` | Staff | New Transaction, My Transaction History |
| `maria`  | `password` | Staff | New Transaction, My Transaction History |

---

## 📁 Project Structure

```
salon-ais/
│
├── index.php                    ← Smart redirect (login or dashboard)
├── login.php                    ← Login page
├── logout.php                   ← Session destroy + redirect
├── setup.php                    ← One-click DB installer (delete after use)
├── database.sql                 ← Full SQL schema + sample data
│
├── config/
│   └── db.php                   ← DB connection + system constants
│
├── auth/
│   └── auth.php                 ← Session helpers, role guards, flash messages
│
├── includes/
│   ├── navbar.php               ← Shared sidebar + topbar (opens <body>)
│   └── footer.php               ← Closes HTML, loads JS
│
├── process/
│   ├── process_login.php        ← Login validation + session creation
│   ├── lookup_customer.php      ← AJAX: check if customer exists by phone
│   ├── process_transaction.php  ← Save TX + details + trigger journal
│   ├── generate_journal.php     ← AIS: double-entry journal generator
│   └── process_void.php         ← Admin: void TX + reversal journal entries
│
├── admin/
│   ├── dashboard.php            ← KPI cards, revenue chart, recent transactions
│   ├── reports.php              ← Financial reports with date filter
│   ├── export_pdf.php           ← Print-to-PDF financial report
│   ├── journals.php             ← General journal ledger view
│   ├── transactions.php         ← All transactions + void action
│   ├── customers.php            ← Customer list + lifetime value
│   ├── services.php             ← Service CRUD (add/edit/toggle)
│   ├── user_management.php      ← User CRUD (admin only)
│   └── change_password.php      ← Admin password change
│
├── staff/
│   ├── transaction.php          ← New transaction entry (Step 1: customer, Step 2: services, Step 3: payment)
│   ├── receipt.php              ← Receipt view + journal entries display + print
│   ├── history.php              ← Staff's own transaction history by date
│   └── change_password.php      ← Staff password change
│
└── assets/
    ├── css/
    │   └── custom.css           ← System-wide styles + print overrides
    └── js/
        └── main.js              ← Toast notifications, sidebar toggle, helpers
```

---

## 🧾 AIS Features Explained

### 1. Automatic Double-Entry Journal Generation
Every completed transaction automatically creates two journal entries:

```
Date        | Account Code | Account Name          | Debit    | Credit
------------|--------------|------------------------|----------|--------
2025-01-15  | 1100         | Cash on Hand           | Rp 500.00  |
2025-01-15  | 4100         | Service Revenue        |          | Rp 500.00
```

For E-Wallet payments:
```
Date        | Account Code | Account Name          | Debit    | Credit
------------|--------------|------------------------|----------|--------
2025-01-15  | 1110         | E-Wallet Receivable    | Rp 800.00  |
2025-01-15  | 4100         | Service Revenue        |          | Rp 800.00
```

### 2. Chart of Accounts
| Code | Account | Type |
|------|---------|------|
| 1100 | Cash on Hand | Asset |
| 1110 | E-Wallet Receivable | Asset |
| 4100 | Service Revenue | Revenue |

### 3. Void Reversal Entries
When a transaction is voided, **reversal journal entries** are automatically posted:
```
Date        | Account Code | Account Name                    | Debit    | Credit
------------|--------------|---------------------------------|----------|--------
2025-01-15  | 4100         | Service Revenue (Reversal)      | Rp 500.00  |
2025-01-15  | 1100         | Cash on Hand (Reversal)        |          | Rp 500.00
```

### 4. Internal Control Flowcharts

**Login Control:**
```
[Enter Credentials] → [Fields Empty?] → Yes → Error
                    → No → [User Exists?] → No → Error
                         → Yes → [Account Active?] → No → Error
                               → Yes → [Password Match?] → No → Error
                                      → Yes → [Set Session] → [Role?]
                                             → Admin → Dashboard
                                             → Staff → New Transaction
```

**Transaction Control:**
```
[Select Services] → [Services Empty?] → Yes → Block
                 → No → [Payment Valid?] → No → Block
                      → Yes → [Customer Exists?] → No → Create New
                             → Yes → [Use Existing] → Save TX
                                                    → Save Details
                                                    → Generate Journal
                                                    → Show Receipt
```

**Report Control:**
```
[Set Date Range] → [From > To?] → Yes → Error
                → No → [To > Today?] → Yes → Error
                     → No → [Valid Period] → Query DB → Show Report
```

---

## 📊 Financial Reports

The **Financial Reports** page (`admin/reports.php`) provides:
- Total Revenue for selected period
- Transaction count
- Cash vs E-Wallet revenue breakdown
- Average transaction value
- Daily revenue average
- Transaction register (full list)
- Top services by revenue with % share
- Export to PDF (print-to-PDF or Dompdf)

The **Journal Ledger** page (`admin/journals.php`) provides:
- All journal entries by date range
- Debit/Credit columns per account
- Balance check (Debits = Credits verification)

---

## 🔐 Role-Based Access Control

| Feature | Admin | Staff |
|---------|-------|-------|
| Dashboard | ✅ | ❌ |
| Financial Reports | ✅ | ❌ |
| Journal Ledger | ✅ | ❌ |
| Export PDF | ✅ | ❌ |
| All Transactions | ✅ | ❌ |
| Void Transaction | ✅ | ❌ |
| Customer List | ✅ | ❌ |
| Service Management | ✅ | ❌ |
| User Management | ✅ | ❌ |
| New Transaction | ✅ | ✅ |
| My Transactions | ✅ | ✅ |
| View Receipt | ✅ | ✅ |
| Change Own Password | ✅ | ✅ |

---

## 🖨 PDF Export

The system includes a **print-ready HTML report** at `admin/export_pdf.php` that works out of the box:
1. Click **Export PDF** on the Reports page
2. In the browser, click **Print / Save as PDF**
3. In the print dialog, choose **Save as PDF** destination

**Optional: True PDF with Dompdf**
If you want actual `.pdf` file downloads, install Dompdf via Composer:
```bash
cd salon-ais
composer require dompdf/dompdf
```
The system automatically detects Dompdf and uses it if available.

---

## ⚙️ Configuration

Edit `config/db.php` to change database settings:
```php
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');       // XAMPP/Laragon default = empty
define('DB_NAME',    'mj_salon_ais');

define('SALON_NAME', 'MJ Salon');
define('SALON_ADDR', '123 Rizal Street, Makati City');
define('SALON_TEL',  '(02) 8123-4567');
define('SALON_EMAIL','info@mjsalon.com');
```

---

## 🐛 Troubleshooting

**"Access denied for user 'root'"**
→ Check that MySQL is running in XAMPP/Laragon Control Panel.
→ Try changing DB_PASS to your MySQL root password if set.

**Blank page / 500 error**
→ Enable PHP error reporting: add `ini_set('display_errors', 1);` at top of index.php temporarily.
→ Check PHP version is 7.4+.

**Styles not loading / 404 on CSS**
→ Make sure the project folder is named exactly `salon-ais`.
→ Check that you opened `http://localhost/salon-ais/` (not just `localhost`).

**Login not working with demo accounts**
→ Run `setup.php` again — it will re-create missing users without deleting existing data.
→ Make sure you're using `password` (not `admin123` — the SQL file uses that as a comment only).

---

## 📚 AIS Concepts Demonstrated

| Concept | Implementation |
|---------|---------------|
| Transaction Processing | `process_transaction.php` — saves TX, details, triggers journal |
| Double-Entry Bookkeeping | `generate_journal.php` — always debit + credit per transaction |
| Internal Controls | Login validation, role guards, payment validation, date validation |
| Audit Trail | All transactions have receipt numbers, timestamps, staff ID |
| Reversals | `process_void.php` — void posts reversal journal entries |
| Financial Reporting | `reports.php` — filterable revenue reports |
| General Ledger | `journals.php` — view all journal entries |
| Role-Based Access | Admin vs Staff redirect; `require_admin()` guards |
| Data Validation | Server-side + client-side on every form |
| Trial Balance | Journal ledger checks Debits = Credits |

---

## 👩‍💻 Developer Notes

- All passwords are hashed with `password_hash(..., PASSWORD_BCRYPT)`.
- Transactions use MySQL transactions (`BEGIN` / `COMMIT` / `ROLLBACK`) for atomicity.
- Receipt numbers follow the format: `MJS-YYYYMMDD-XXXXX` (e.g. `MJS-20250115-A3F7K`).
- The AJAX customer lookup uses `fetch()` and returns JSON.
- `base_url()` in `auth.php` auto-detects the project subfolder for proper link generation.

---

*MJ Salon AIS — Built for AIS academic presentations and real-world salon operations.*
*PHP Native · MySQL · Bootstrap 5 · Laragon/XAMPP*
