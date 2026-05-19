<?php
// ============================================================
// MJ Salon AIS - Database Configuration
// config/db.php
// ============================================================

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');          // Laragon default: '' | XAMPP default: ''
define('DB_NAME',     'mj_salon_ais');
define('DB_CHARSET',  'utf8mb4');

// System Constants
define('SALON_NAME',  'MJ Salon');
define('SALON_ADDR',  '123 Rizal Street, Makati City, Philippines');
define('SALON_TEL',   '(02) 8123-4567');
define('SALON_EMAIL', 'info@mjsalon.com');
define('TAX_RATE',    0);           // 0 = no VAT displayed; set 0.12 for 12% VAT

/**
 * Create and return a MySQLi connection.
 * Terminates with an error message if connection fails.
 *
 * @return mysqli
 */
function db_connect(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        // Internal Control: System halts on DB failure
        die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">'
          . '<h2>⚠ Database Connection Failed</h2>'
          . '<p>Could not connect to the database. Please check your Laragon/XAMPP server.</p>'
          . '<p><small>' . htmlspecialchars($conn->connect_error) . '</small></p>'
          . '</div>');
    }

    $conn->set_charset(DB_CHARSET);
    return $conn;
}
