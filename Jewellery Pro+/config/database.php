<?php
// Disable temporary exception throwing to handle fallback connection gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Configure your Database Credentials here:
$db_name = 'moti';

// List of credential sets to attempt (aaPanel VPS, Hostinger, XAMPP Local)
$credentials = [
    // [Host, User, Password]
    ['127.0.0.1', 'root', 'RootPass123'], // Hostinger / aaPanel VPS default
    ['localhost', 'root', 'RootPass123'],
    ['127.0.0.1', 'root', ''],            // XAMPP Local default (empty password)
    ['localhost', 'root', ''],
];

$conn = false;
$connection_errors = [];

foreach ($credentials as $cred) {
    list($h, $u, $p) = $cred;
    
    // 1. Attempt connecting directly to target database
    $c = @mysqli_connect($h, $u, $p, $db_name);
    if ($c) {
        $conn = $c;
        $host = $h;
        $user = $u;
        $password = $p;
        $database = $db_name;
        break;
    } 
    
    // 2. If database doesn't exist yet, attempt connecting to MySQL server to create database automatically
    $c_nodb = @mysqli_connect($h, $u, $p);
    if ($c_nodb) {
        @mysqli_query($c_nodb, "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        if (@mysqli_select_db($c_nodb, $db_name)) {
            $conn = $c_nodb;
            $host = $h;
            $user = $u;
            $password = $p;
            $database = $db_name;
            break;
        }
    }
    
    $err = mysqli_connect_error() ?: 'Access denied or server unreachable';
    $connection_errors[] = "$h ($u): $err";
}

// Re-enable exceptions for standard behavior
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!$conn) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Database Connection Error</title>';
    echo '<style>body{font-family:"Poppins",sans-serif;background:#0d1117;color:#c9d1d9;padding:40px;line-height:1.6;}';
    echo '.card{background:#161b22;padding:30px;border-radius:12px;max-width:720px;margin:0 auto;border:1px solid #30363d;box-shadow:0 10px 30px rgba(0,0,0,0.5);}';
    echo 'h2{color:#f85149;margin-top:0;display:flex;align-items:center;gap:10px;}';
    echo 'code{background:#21262d;padding:4px 8px;border-radius:6px;color:#ec6cb9;font-family:monospace;font-size:14px;}';
    echo 'ul{margin-top:10px;padding-left:20px;} li{margin-bottom:12px;}';
    echo '.diagnostic{background:#0d1117;padding:12px;border-radius:8px;border:1px solid #21262d;color:#8b949e;font-size:13px;word-break:break-all;}';
    echo '</style></head><body>';
    echo '<div class="card">';
    echo '<h2> Database Connection Failed</h2>';
    echo '<p>Unable to connect to MySQL database <code>' . htmlspecialchars($db_name) . '</code> on aaPanel / VPS or Local server.</p>';
    echo '<h4>Diagnostic details:</h4>';
    echo '<div class="diagnostic">' . htmlspecialchars(implode(' | ', $connection_errors)) . '</div>';
    echo '<h3> How to Fix on aaPanel / VPS / XAMPP:</h3>';
    echo '<ul>';
    echo '<li><strong>Restart MySQL service:</strong><br>Run on server terminal: <code>/etc/init.d/mysqld restart</code> or <code>systemctl restart mysqld</code></li>';
    echo '<li><strong>Find your MySQL Root Password:</strong><br>In aaPanel dashboard &rarr; <em>Databases</em> &rarr; <em>Root Password</em>, or run: <code>cat /www/server/pass.txt</code></li>';
    echo '<li><strong>Update password in config/database.php:</strong><br>Add your password to <code>$credentials</code> in <code>config/database.php</code>.</li>';
    echo '</ul>';
    echo '</div></body></html>';
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');
if ($conn) { @mysqli_query($conn, "SET time_zone = '+05:30'"); }

// Create tables if not exist
$create_users = "CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
mobile VARCHAR(15) UNIQUE NOT NULL,
email VARCHAR(100),
password VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_users);

$create_products = "CREATE TABLE IF NOT EXISTS products (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(200) NOT NULL,
category VARCHAR(50),
price DECIMAL(10,2) NOT NULL,
quantity INT DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_products);

// Ensure required columns exist on products table
$products_cols = [
    'serial_no'  => "VARCHAR(50) NULL",
    'weight'     => "VARCHAR(20) NULL",
    'item_name'  => "VARCHAR(255) DEFAULT ''",
    'huid_code'  => "VARCHAR(100) NULL",
    'hsn_code'   => "VARCHAR(50) DEFAULT '0'",
    'unit'       => "VARCHAR(20) DEFAULT 'pcs'",
    'updated_at' => "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
];

foreach ($products_cols as $col_name => $col_definition) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE '$col_name'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        $unique = ($col_name === 'serial_no') ? " UNIQUE" : "";
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN $col_name $col_definition$unique");
    }
}

$create_customers = "CREATE TABLE IF NOT EXISTS customers (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
mobile VARCHAR(15) UNIQUE NOT NULL,
email VARCHAR(100),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_customers);

// Ensure required columns exist on customers table
$customers_cols = [
    'address'    => "TEXT NULL",
    'email'      => "VARCHAR(255) NULL",
    'gst_number' => "VARCHAR(20) DEFAULT ''",
];

foreach ($customers_cols as $col_name => $col_definition) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM customers LIKE '$col_name'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "ALTER TABLE customers ADD COLUMN $col_name $col_definition");
    }
}

$create_contacts = "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(100),
    address TEXT,
    gst_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_contacts);

$chk_note = mysqli_query($conn, "SHOW COLUMNS FROM contacts LIKE 'note'");
if ($chk_note && mysqli_num_rows($chk_note) == 0) {
    mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN note TEXT");
}

// Sanchari Scheme Tables (Moti Jewellers)
$create_sanchari_customers = "CREATE TABLE IF NOT EXISTS sanchari_customers (
id INT AUTO_INCREMENT PRIMARY KEY,
customer_id VARCHAR(20) UNIQUE NOT NULL,
book_id VARCHAR(20) UNIQUE NOT NULL,
customer_name VARCHAR(100) NOT NULL,
mobile VARCHAR(15) NOT NULL,
email VARCHAR(100),
address TEXT,
joining_date DATE NOT NULL,
monthly_amount DECIMAL(10,2) NOT NULL,
scheme_duration VARCHAR(20) NOT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'Active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_sanchari_customers);

$create_sanchari_payments = "CREATE TABLE IF NOT EXISTS sanchari_payments (
id INT AUTO_INCREMENT PRIMARY KEY,
payment_id VARCHAR(20) UNIQUE NOT NULL,
customer_id VARCHAR(20) NOT NULL,
book_id VARCHAR(20) NOT NULL,
customer_name VARCHAR(100) NOT NULL,
installment_no INT NOT NULL,
payment_date DATE NOT NULL,
amount DECIMAL(10,2) NOT NULL,
balance_amount DECIMAL(12,2) DEFAULT 0.00,
paid_amount DECIMAL(12,2) DEFAULT 0.00,
payment_status VARCHAR(20) DEFAULT 'paid',
payment_mode VARCHAR(50) DEFAULT 'Cash',
gold_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
weight DECIMAL(10,3) NOT NULL DEFAULT 0,
remarks TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_sanchari_payments);

$create_sanchari_redemptions = "CREATE TABLE IF NOT EXISTS sanchari_redemptions (
id INT AUTO_INCREMENT PRIMARY KEY,
redemption_id VARCHAR(20) UNIQUE NOT NULL,
customer_id VARCHAR(20) NOT NULL,
book_id VARCHAR(20) NOT NULL,
purchase_date DATE NOT NULL,
item_name VARCHAR(150) NOT NULL,
gross_weight DECIMAL(10,3) DEFAULT 0,
net_weight DECIMAL(10,3) DEFAULT 0,
making_charge DECIMAL(10,2) DEFAULT 0,
gst DECIMAL(10,2) DEFAULT 0,
jewellery_amount DECIMAL(10,2) DEFAULT 0,
adjusted_amount DECIMAL(10,2) DEFAULT 0,
balance_amount DECIMAL(10,2) DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_sanchari_redemptions);

$create_invoices = "CREATE TABLE IF NOT EXISTS invoices (
id INT AUTO_INCREMENT PRIMARY KEY,
invoice_no VARCHAR(50) UNIQUE NOT NULL,
customer_name VARCHAR(100) NOT NULL,
customer_mobile VARCHAR(15) NOT NULL,
gst_type ENUM('gst', 'non_gst') DEFAULT 'non_gst',
subtotal DECIMAL(10,2) DEFAULT 0,
gst_amount DECIMAL(10,2) DEFAULT 0,
total_amount DECIMAL(10,2) DEFAULT 0,
created_by INT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_invoices);
@mysqli_query($conn, "ALTER TABLE invoices MODIFY COLUMN gst_type VARCHAR(50) DEFAULT 'non_gst'");

// Ensure all required columns exist on invoices table (Unified GST & Non-GST with Advance Adjustment)
$columns_to_ensure = [
    'customer_id'        => "INT NULL",
    'customer_address'   => "TEXT NULL",
    'customer_gstin'     => "VARCHAR(50) NULL",
    'discount'           => "DECIMAL(10,2) DEFAULT 0",
    'payment_status'     => "VARCHAR(20) DEFAULT 'pending'",
    'payment_method'     => "VARCHAR(20) DEFAULT 'Cash'",
    'paid_amount'        => "DECIMAL(10,2) DEFAULT 0",
    'balance_amount'     => "DECIMAL(10,2) DEFAULT 0",
    'huid_code'          => "VARCHAR(100) NULL",
    'cash_paid'          => "DECIMAL(10,2) DEFAULT 0",
    'upi_paid'           => "DECIMAL(10,2) DEFAULT 0",
    'account_paid'       => "DECIMAL(10,2) DEFAULT 0",
    'cheque_paid'        => "DECIMAL(10,2) DEFAULT 0",
    'due_date'           => "DATE NULL",
    'reminder_sent'      => "TINYINT(1) DEFAULT 0",
    'pdf_file'           => "LONGBLOB NULL",
    'pdf_file_name'      => "VARCHAR(255) NULL",
    'old_gold_value'     => "DECIMAL(10,2) DEFAULT 0.00",
    'old_gold_amount'    => "DECIMAL(10,2) DEFAULT 0.00",
    'old_gold_type'      => "VARCHAR(50) DEFAULT 'Old Gold'",
    'advance_adjusted'   => "DECIMAL(10,2) DEFAULT 0.00",
    'advance_id'         => "INT NULL",
    'applied_advance_id' => "INT NULL",
    'round_off'          => "DECIMAL(10,2) DEFAULT 0.00",
    'is_split'           => "TINYINT(1) DEFAULT 0",
    'bill_type'          => "VARCHAR(10) DEFAULT 'invoice'",
];

foreach ($columns_to_ensure as $col_name => $col_definition) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM invoices LIKE '$col_name'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "ALTER TABLE invoices ADD COLUMN $col_name $col_definition");
    }
}

// Ensure customer_advances table exists
$create_customer_advances = "CREATE TABLE IF NOT EXISTS customer_advances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_mobile VARCHAR(20) NOT NULL,
    customer_address TEXT NULL,
    advance_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    used_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    available_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    cash_paid DECIMAL(12,2) DEFAULT 0.00,
    upi_paid DECIMAL(12,2) DEFAULT 0.00,
    booking_note TEXT NULL,
    delivery_date DATE NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_customer_advances);

// Ensure advance_adjustments table exists
$create_advance_adjustments = "CREATE TABLE IF NOT EXISTS advance_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advance_id INT NOT NULL,
    invoice_no VARCHAR(50) NOT NULL,
    adjusted_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    surplus_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    surplus_action VARCHAR(50) DEFAULT 'credit',
    refund_mode VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_advance_adjustments);

$create_invoice_items = "CREATE TABLE IF NOT EXISTS invoice_items (
id INT AUTO_INCREMENT PRIMARY KEY,
invoice_id INT,
product_id INT,
quantity DECIMAL(10,3) DEFAULT 1,
price DECIMAL(10,2),
total DECIMAL(10,2)
)";
mysqli_query($conn, $create_invoice_items);

// Ensure required columns exist on invoice_items table
$invoice_items_cols = [
    'product_name'      => "VARCHAR(200) NULL",
    'serial_no'         => "VARCHAR(100) NULL",
    'hsn_code'          => "VARCHAR(50) NULL",
    'making_charge'     => "DECIMAL(10,2) DEFAULT 0",
    'making_charge_pct' => "DECIMAL(5,2) DEFAULT 0",
    'hallmark'          => "DECIMAL(10,2) DEFAULT 0",
    'hallmark_type'     => "VARCHAR(50) DEFAULT '22K'",
    'discount'          => "DECIMAL(10,2) DEFAULT 0",
    'huid_code'         => "VARCHAR(100) NULL",
    'pcs'               => "INT DEFAULT 1",
    'unit'              => "VARCHAR(20) DEFAULT 'g'",
    'gross_wt'          => "DECIMAL(10,3) DEFAULT 0.000",
    'net_wt'            => "DECIMAL(10,3) DEFAULT 0.000",
    'gst_type'          => "VARCHAR(20) DEFAULT 'non_gst'",
    'diamond_cent'      => "VARCHAR(50) DEFAULT ''",
    'metal_kt'          => "VARCHAR(50) DEFAULT ''",
    'metal_weight'      => "VARCHAR(50) DEFAULT ''",
];

foreach ($invoice_items_cols as $col_name => $col_definition) {
    $chk = mysqli_query($conn, "SHOW COLUMNS FROM invoice_items LIKE '$col_name'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "ALTER TABLE invoice_items ADD COLUMN $col_name $col_definition");
    }
}

// Modify quantity column in invoice_items if it is not DECIMAL
$chk_qty = mysqli_query($conn, "SHOW COLUMNS FROM invoice_items LIKE 'quantity'");
if ($chk_qty && mysqli_num_rows($chk_qty) > 0) {
    $row = mysqli_fetch_assoc($chk_qty);
    if (stripos($row['Type'] ?? '', 'decimal') === false) {
        mysqli_query($conn, "ALTER TABLE invoice_items MODIFY COLUMN quantity DECIMAL(10,3) NULL");
    }
}

// Ensure due_update_history table exists
$create_due_history = "CREATE TABLE IF NOT EXISTS due_update_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    previous_balance DECIMAL(10,2) NOT NULL,
    new_balance DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_mode VARCHAR(50) DEFAULT 'Cash'
)";
mysqli_query($conn, $create_due_history);

$chkModeCol = mysqli_query($conn, "SHOW COLUMNS FROM due_update_history LIKE 'payment_mode'");
if ($chkModeCol && mysqli_num_rows($chkModeCol) == 0) {
    @mysqli_query($conn, "ALTER TABLE due_update_history ADD COLUMN payment_mode VARCHAR(50) DEFAULT 'Cash'");
}

// Ensure income and expenses tables exist
$create_income = "CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    income_date DATE NOT NULL,
    source VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    payment_method VARCHAR(30) DEFAULT 'cash',
    invoice_no VARCHAR(50) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_income);

$create_expenses = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    payment_method VARCHAR(30) DEFAULT 'cash',
    bill_no VARCHAR(50) NULL,
    vendor_name VARCHAR(100) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_expenses);

// Ensure income_categories and expense_categories exist
$create_income_cat = "CREATE TABLE IF NOT EXISTS income_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'active'
)";
mysqli_query($conn, $create_income_cat);

$create_expense_cat = "CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'active'
)";
mysqli_query($conn, $create_expense_cat);

// Seed default categories if empty
$chk_inc_cat = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM income_categories");
if ($chk_inc_cat && ($row_cat = mysqli_fetch_assoc($chk_inc_cat)) && ($row_cat['cnt'] ?? 0) == 0) {
    foreach (['Sales Income', 'Interest Income', 'Rental Income', 'Commission Income', 'Other Income'] as $c) {
        mysqli_query($conn, "INSERT IGNORE INTO income_categories (category_name) VALUES ('$c')");
    }
}
$chk_exp_cat = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM expense_categories");
if ($chk_exp_cat && ($row_cat = mysqli_fetch_assoc($chk_exp_cat)) && ($row_cat['cnt'] ?? 0) == 0) {
    foreach (['Purchase', 'Rent', 'Electricity Bill', 'Salary', 'Marketing', 'Maintenance', 'Tax Payment', 'Transportation', 'Other Expenses'] as $c) {
        mysqli_query($conn, "INSERT IGNORE INTO expense_categories (category_name) VALUES ('$c')");
    }
}

// Ensure advance_customers table exists (Advance Token Booking)
$create_advance_customers = "CREATE TABLE IF NOT EXISTS advance_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    customer_name VARCHAR(100) NULL,
    customer_mobile VARCHAR(15) NULL,
    advance_amount DECIMAL(10,2) DEFAULT 0.00,
    advance_date DATE NULL,
    due_date DATE NULL,
    reminder_days INT DEFAULT 3,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_advance_customers);

// Ensure stock_transfers table exists (Branch Stock Transfers)
$create_stock_transfers = "CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transfer_no VARCHAR(50) UNIQUE NOT NULL,
    destination_shop VARCHAR(150) NOT NULL,
    transfer_date DATE NOT NULL,
    entry_mode VARCHAR(20) DEFAULT 'stock',
    product_id INT DEFAULT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    weight DECIMAL(10,3) DEFAULT 0.000,
    quantity INT DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'pcs',
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    item_value DECIMAL(12,2) DEFAULT 0.00,
    huid_code VARCHAR(100) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_stock_transfers);

// Ensure system_settings table exists
$create_system_settings = "CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_system_settings);

// Create purchase_entries table if not exists (Moti Jewellers as Buyer)
$create_purchase_entries = "CREATE TABLE IF NOT EXISTS purchase_entries (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    purchase_no     VARCHAR(50)  NOT NULL UNIQUE,
    purchase_date   DATE         NOT NULL,
    invoice_no      VARCHAR(100) NOT NULL,
    invoice_date    DATE         NOT NULL,
    ref_no          VARCHAR(100),
    ref_date        DATE,
    payment_mode    VARCHAR(50)  DEFAULT 'NEFT/RTGS',
    supplier_name   VARCHAR(200) NOT NULL,
    supplier_addr   VARCHAR(500),
    supplier_gstin  VARCHAR(20),
    supplier_pan    VARCHAR(20),
    supplier_state  VARCHAR(100),
    supplier_state_code VARCHAR(5),
    supplier_mobile VARCHAR(20),
    supplier_email  VARCHAR(100),
    buyer_name      VARCHAR(200) DEFAULT 'MOTI JEWELLERS',
    buyer_addr      VARCHAR(500) DEFAULT 'Hamirpur, Balichak Station Road, Paschim Medinipur, West Bengal - 721148',
    buyer_gstin     VARCHAR(20)  DEFAULT '19AEPPM9851A1Z4',
    buyer_pan       VARCHAR(20)  DEFAULT 'AEPPM9851A',
    material_type   ENUM('Gold','Silver','Diamond','Platinum') NOT NULL,
    description     VARCHAR(300),
    huid_code       VARCHAR(100),
    hsn_sac         VARCHAR(20),
    qty             DECIMAL(12,4) NOT NULL,
    unit            VARCHAR(10)  DEFAULT 'gm',
    rate_per_unit   DECIMAL(12,4) NOT NULL,
    tax_type        ENUM('CGST_SGST','IGST') DEFAULT 'CGST_SGST',
    cgst_pct        DECIMAL(5,2) DEFAULT 1.50,
    sgst_pct        DECIMAL(5,2) DEFAULT 1.50,
    igst_pct        DECIMAL(5,2) DEFAULT 3.00,
    subtotal        DECIMAL(14,2),
    cgst_amt        DECIMAL(14,2) DEFAULT 0,
    sgst_amt        DECIMAL(14,2) DEFAULT 0,
    igst_amt        DECIMAL(14,2) DEFAULT 0,
    gst_total       DECIMAL(14,2),
    total_amount    DECIMAL(14,2),
    amount_words    VARCHAR(500),
    notes           TEXT,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_purchase_entries);

// Ensure purchase_items table exists for multi-item inward procurement
$create_purchase_items = "CREATE TABLE IF NOT EXISTS purchase_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id   INT NOT NULL,
    material_type ENUM('Gold','Silver','Diamond','Platinum') NOT NULL,
    huid_code     VARCHAR(50) DEFAULT '',
    description   VARCHAR(300) DEFAULT NULL,
    hsn_sac       VARCHAR(20) DEFAULT NULL,
    qty           DECIMAL(12,4) NOT NULL,
    unit          VARCHAR(10) DEFAULT 'gm',
    rate_per_unit DECIMAL(12,4) NOT NULL,
    tax_type      ENUM('CGST_SGST','IGST') DEFAULT 'CGST_SGST',
    cgst_pct      DECIMAL(5,2) DEFAULT 1.50,
    sgst_pct      DECIMAL(5,2) DEFAULT 1.50,
    igst_pct      DECIMAL(5,2) DEFAULT 3.00,
    subtotal      DECIMAL(14,2) DEFAULT NULL,
    cgst_amt      DECIMAL(14,2) DEFAULT 0.00,
    sgst_amt      DECIMAL(14,2) DEFAULT 0.00,
    igst_amt      DECIMAL(14,2) DEFAULT 0.00,
    gst_total     DECIMAL(14,2) DEFAULT NULL,
    total_amount  DECIMAL(14,2) DEFAULT NULL,
    amount_words  VARCHAR(500) DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_purchase (purchase_id)
)";
mysqli_query($conn, $create_purchase_items);

$chk_huid = mysqli_query($conn, "SHOW COLUMNS FROM purchase_entries LIKE 'huid_code'");
if ($chk_huid && mysqli_num_rows($chk_huid) == 0) {
    mysqli_query($conn, "ALTER TABLE purchase_entries ADD COLUMN huid_code VARCHAR(100) NULL AFTER description");
}

// Create stock_metal table if not exists
$create_stock_metal = "CREATE TABLE IF NOT EXISTS stock_metal (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    material_type ENUM('Gold','Silver','Diamond','Platinum') NOT NULL,
    unit          VARCHAR(10) DEFAULT 'gm',
    qty_available DECIMAL(14,4) DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_material (material_type)
)";
mysqli_query($conn, $create_stock_metal);

// Seed stock metal rows if empty
foreach (['Gold', 'Silver', 'Diamond', 'Platinum'] as $m) {
    mysqli_query($conn, "INSERT IGNORE INTO stock_metal (material_type, qty_available) VALUES ('$m', 0)");
}

// Ensure whatsapp tables exist
$create_whatsapp_settings = "CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_type VARCHAR(50) DEFAULT 'greenapi',
    api_url VARCHAR(255) NULL,
    api_token VARCHAR(255) NULL,
    instance_id VARCHAR(100) NULL,
    phone_number_id VARCHAR(100) NULL,
    access_token TEXT NULL,
    status VARCHAR(20) DEFAULT 'inactive',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reminder_days INT DEFAULT 3
)";
mysqli_query($conn, $create_whatsapp_settings);

$create_whatsapp_logs = "CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_number VARCHAR(20) NOT NULL,
    recipient_name VARCHAR(100) NULL,
    message_type VARCHAR(50) NULL,
    message_content TEXT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    api_response TEXT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    media_file_path TEXT NULL,
    media_file_name VARCHAR(255) NULL
)";
mysqli_query($conn, $create_whatsapp_logs);

$create_otp_logins = "CREATE TABLE IF NOT EXISTS otp_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_otp_logins);

$create_password_resets = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_password_resets);

// Ensure default admin user exists
$create_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_users);

$chk_admin = mysqli_query($conn, "SELECT id FROM users WHERE email='motijewellers9635985848@gmail.com'");
if (!$chk_admin || mysqli_num_rows($chk_admin) == 0) {
    $default_hash = password_hash('123456', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name, mobile, email, password, role) VALUES ('MOTI JEWELLERS', '9635985848', 'motijewellers9635985848@gmail.com', '$default_hash', 'admin')");
}

// Ensure activity logger exists and sync initial stock logs
if (file_exists(__DIR__ . '/../includes/activity_logger.php')) {
    require_once __DIR__ . '/../includes/activity_logger.php';
    init_activity_log_table($conn);
    sync_missing_activity_logs($conn);
}
?>
