<?php
session_start();
require_once 'config/database.php';
if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$logo_paths = ['logo.png', 'assets/images/logo.png', 'images/logo.png', 'assets/images/moti-removebg-preview.png'];

// Ensure required columns exist on sanchari_payments
$chk_bal = mysqli_query($conn, "SHOW COLUMNS FROM sanchari_payments LIKE 'balance_amount'");
if ($chk_bal && mysqli_num_rows($chk_bal) == 0) {
    mysqli_query($conn, "ALTER TABLE sanchari_payments ADD COLUMN balance_amount DECIMAL(10,2) DEFAULT 0");
}
$chk_paid = mysqli_query($conn, "SHOW COLUMNS FROM sanchari_payments LIKE 'paid_amount'");
if ($chk_paid && mysqli_num_rows($chk_paid) == 0) {
    mysqli_query($conn, "ALTER TABLE sanchari_payments ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0");
}
$chk_st = mysqli_query($conn, "SHOW COLUMNS FROM sanchari_payments LIKE 'payment_status'");
if ($chk_st && mysqli_num_rows($chk_st) == 0) {
    mysqli_query($conn, "ALTER TABLE sanchari_payments ADD COLUMN payment_status VARCHAR(20) DEFAULT 'paid'");
}

// Handle payment update
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_payment'])) {
    $inv_id = (int)$_POST['inv_id'];
    $paid = (float)$_POST['paid_amount'];
    $mode = mysqli_real_escape_string($conn, trim($_POST['payment_mode']));
    $res = mysqli_query($conn, "SELECT amount FROM sanchari_payments WHERE id=$inv_id");
    $row = mysqli_fetch_assoc($res);
    $total = $row ? (float)$row['amount'] : 0;
    $balance = $total - $paid;
    $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
    mysqli_query($conn, "UPDATE sanchari_payments SET paid_amount=$paid, balance_amount=$balance, payment_status='$status', payment_mode='$mode' WHERE id=$inv_id");
    header('Location: sanchari_dashboard.php?tab=payments&msg=updated');
    exit;
}

// Handle status update
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {
    $cid = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
    $st  = mysqli_real_escape_string($conn, trim($_POST['status']));
    if(in_array($st, ['Active','Completed','Closed'])) {
        mysqli_query($conn, "UPDATE sanchari_customers SET status='$st' WHERE customer_id='$cid'");
    }
    header('Location: sanchari_dashboard.php?tab=customers&msg=updated');
    exit;
}

// Handle customer delete
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_customer'])) {
    $cid = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
    if($cid !== '') {
        mysqli_query($conn, "DELETE FROM sanchari_payments WHERE customer_id='$cid'");
        mysqli_query($conn, "DELETE FROM sanchari_redemptions WHERE customer_id='$cid'");
        mysqli_query($conn, "DELETE FROM sanchari_customers WHERE customer_id='$cid'");
    }
    header('Location: sanchari_dashboard.php?tab=customers&msg=deleted');
    exit;
}

$tab = $_GET['tab'] ?? 'overview';

// -- Stats ------------------------------------------------------------------
$total_customers   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sanchari_customers"))['c'] ?? 0;
$active_customers  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sanchari_customers WHERE status='Active'"))['c'] ?? 0;
$total_collected   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) s FROM sanchari_payments"))['s'] ?? 0;
$total_weight      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(weight),0) s FROM sanchari_payments"))['s'] ?? 0;
$pending_balance   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(balance_amount),0) s FROM sanchari_payments WHERE payment_status!='paid'"))['s'] ?? 0;
$this_month        = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) s FROM sanchari_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())"))['s'] ?? 0;

// -- Data for tabs --------------------------------------------------------
$customers_data = mysqli_query($conn,"SELECT sc.*, COALESCE(SUM(sp.amount),0) total_paid, COUNT(sp.id) installments FROM sanchari_customers sc LEFT JOIN sanchari_payments sp ON sc.customer_id=sp.customer_id GROUP BY sc.id ORDER BY sc.id DESC");
$payments_data  = mysqli_query($conn,"SELECT * FROM sanchari_payments ORDER BY id DESC LIMIT 200");

// PDF download for customer statement
if(isset($_GET['pdf']) && isset($_GET['cid'])) {
    $cid = mysqli_real_escape_string($conn, $_GET['cid']);
    $cust = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM sanchari_customers WHERE customer_id='$cid'"));
    $pays = mysqli_query($conn,"SELECT * FROM sanchari_payments WHERE customer_id='$cid' ORDER BY payment_date ASC");
    $rows = [];
    while($r = mysqli_fetch_assoc($pays)) $rows[] = $r;
    $total_p = array_sum(array_column($rows,'amount'));
    $total_w = array_sum(array_column($rows,'weight'));
    ob_start();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Statement</title>
<style>
  body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:20px;color:#222;}
  h2{color:#800020;margin:0 0 4px;}
  .info{margin-bottom:16px;border:1px solid #eee;padding:10px;border-radius:6px;}
  table{width:100%;border-collapse:collapse;margin-top:10px;}
  th{background:#800020;color:#fff;padding:7px 8px;text-align:left;}
  td{padding:6px 8px;border-bottom:1px solid #f0e8e0;}
  tr:nth-child(even) td{background:#fdf6f0;}
  .total{font-weight:normal;background:#fff8f4;}
  .footer{margin-top:20px;font-size:10px;color:#999;text-align:center;}
</style></head><body>
<h2>Moti Jewellers - Swarna Sanchay Statement</h2>
<div class="info">
  <strong><?= htmlspecialchars($cust['customer_name'] ?? '') ?></strong> &nbsp;|&nbsp;
  Book ID: <?= htmlspecialchars($cust['book_id'] ?? '') ?> &nbsp;|&nbsp;
  Mobile: <?= htmlspecialchars($cust['mobile'] ?? '') ?><br>
  Joining: <?= htmlspecialchars($cust['joining_date'] ?? '') ?> &nbsp;|&nbsp;
  Monthly: ₹<?= number_format($cust['monthly_amount'] ?? 0, 2) ?> &nbsp;|&nbsp;
  Status: <?= htmlspecialchars($cust['status'] ?? '') ?>
</div>
<table>
  <thead><tr><th>#</th><th>Payment ID</th><th>Date</th><th>Installment</th><th>Amount (₹)</th><th>Gold Rate</th><th>Weight (g)</th><th>Mode</th></tr></thead>
  <tbody>
  <?php foreach($rows as $i=>$r): ?>
  <tr><td><?=$i+1?></td><td><?=htmlspecialchars($r['payment_id'])?></td><td><?=htmlspecialchars($r['payment_date'])?></td><td><?=$r['installment_no']?></td><td><?=number_format($r['amount'],2)?></td><td><?=number_format($r['gold_rate'],2)?></td><td><?=number_format($r['weight'],3)?></td><td><?=htmlspecialchars($r['payment_mode'])?></td></tr>
  <?php endforeach; ?>
  <tr class="total"><td colspan="4">Total</td><td>₹<?=number_format($total_p,2)?></td><td>-</td><td><?=number_format($total_w,3)?>g</td><td></td></tr>
  </tbody>
</table>
<div class="footer">Generated on <?=date('d M Y H:i')?> | Moti Jewellers</div>
<script>window.onload=()=>window.print();</script>
</body></html>
<?php
    $html = ob_get_clean();
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>Sanchay Scheme Dashboard - MOTI JEWELLERS</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap');
      * { font-family: 'Poppins', sans-serif; box-sizing: border-box; }
      h1,h2,h3,.gold-font { font-family: 'Playfair Display', serif; }

      /* SIDEBAR */
      .sidebar {
          position: fixed; top: 0; left: 0; width: 240px; height: 100vh;
          background: linear-gradient(180deg, #011921 0%, #03373b 50%, #044e54 80%, #011921 100%);
          z-index: 1000; display: flex; flex-direction: column;
          box-shadow: 4px 0 24px rgba(0,0,0,0.25);
          transition: transform 0.35s cubic-bezier(.4,0,.2,1);
          overflow: hidden;
      }
      .sidebar-logo {
          padding: 22px 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.18);
          display: flex; align-items: center; gap: 12px; flex-shrink: 0;
      }
      .sidebar-logo img { width: 44px; height: 44px; object-fit: contain; border-radius: 50%; background: rgba(255,255,255,0.1); }
      .sidebar-logo-text h2 { color: #fff; font-size: 13px; font-weight: 700; line-height: 1.3; font-family: 'Playfair Display', serif; }
      .sidebar-logo-text p { color: rgba(255,255,255,0.65); font-size: 10px; margin-top: 1px; }
      .sidebar-nav { flex: 1; padding: 10px 0; overflow-y: auto; overflow-x: hidden; }
      .sidebar-section-label { padding: 10px 20px 4px; color: rgba(255,255,255,0.45); font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; position: sticky; top: 0; background: #011921; color: #f5c842; z-index: 10; }
      .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 11px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s ease; border-left: 3px solid transparent; position: relative; }
      .sidebar-nav a:hover { background: rgba(255,255,255,0.13); color: #fff; border-left-color: rgba(255,255,255,0.8); padding-left: 26px; }
      .sidebar-nav a.active { background: rgba(255,255,255,0.22); color: #fff; border-left-color: #fff; font-weight: 700; }
      .sidebar-nav a i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
      .sidebar-divider { height: 1px; background: rgba(255,255,255,0.12); margin: 6px 16px; }
      .sidebar-user { padding: 14px 16px 18px; border-top: 1px solid rgba(255,255,255,0.18); background: rgba(0,0,0,0.12); flex-shrink: 0; }
      .sidebar-user-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
      .sidebar-user-info i { color: rgba(255,255,255,0.9); font-size: 26px; }
      .sidebar-user-info .user-details p { color: #fff; font-size: 12px; font-weight: 600; }
      .sidebar-user-info .user-details span { color: rgba(255,255,255,0.55); font-size: 10px; }
      .sidebar-logout { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 14px; background: rgba(239,68,68,0.75); color: #fff; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
      .sidebar-logout:hover { background: #ef4444; color: #fff; }
      .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px); }
      .sidebar-overlay.active { display: block; }
      .page-wrapper { margin-left: 240px; min-height: 100vh; transition: margin-left 0.35s ease; }
      nav.nav-gold { background: linear-gradient(135deg, #011921, #03373b) !important; border-bottom: 2.5px solid #ffd700; box-shadow: 0 0 12px rgba(255, 215, 0, 0.5) !important; transition: margin-left 0.35s ease; }

      /* BURGER MENU */
      .burger-menu { width: 28px; height: 20px; position: relative; cursor: pointer; }
      .burger-menu span { display: block; position: absolute; height: 3px; width: 100%; background: #fff; border-radius: 3px; transition: all 0.3s ease; }
      .burger-menu span:nth-child(1) { top: 0; }
      .burger-menu span:nth-child(2) { top: 9px; }
      .burger-menu span:nth-child(3) { top: 18px; }
      .burger-menu.active span:nth-child(1) { top: 9px; transform: rotate(135deg); }
      .burger-menu.active span:nth-child(2) { opacity: 0; left: -20px; }
      .burger-menu.active span:nth-child(3) { top: 9px; transform: rotate(-135deg); }

      /* CARDS & BUTTONS */
      .jewel-card { background: linear-gradient(145deg, #fdf6e3, #f5ead0); border-radius: 20px; border: 1px solid rgba(181,115,14,0.25); box-shadow: 0 8px 30px rgba(181,115,14,0.1); }
      .stat-gem {
          background: rgba(255, 255, 255, 0.7) !important;
          backdrop-filter: blur(12px) !important;
          border: 1px solid rgba(181,115,14,0.25) !important;
          border-radius: 18px; padding: 16px; text-align: center;
          transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(181,115,14,0.06) !important;
      }
      .stat-gem:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(181,115,14,0.15) !important; border-color: #d68b16 !important; }

      .btn-jewel {
          background: linear-gradient(135deg, #800020, #d68b16);
          border: none; border-radius: 50px; padding: 10px 24px;
          font-weight: 700; color: #fff; transition: all 0.3s ease;
          display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 13px;
      }
      .btn-jewel:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(214,139,22,0.4); color: #fff; }
      .btn-gold {
          background: linear-gradient(135deg, #d68b16, #b5730e);
          border: none; border-radius: 50px; padding: 10px 24px;
          font-weight: 700; color: #fff; transition: all 0.3s ease;
          display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 13px;
      }
      .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(214,139,22,0.4); color: #fff; }

      /* TABS */
      .nav-tab-gold { display: flex; gap: 8px; border-bottom: 2px solid rgba(181,115,14,0.2); margin-bottom: 24px; flex-wrap: wrap; }
      .tab-item { padding: 10px 18px; font-size: 13px; font-weight: 600; color: #7a4e0a; text-decoration: none; border-radius: 12px 12px 0 0; transition: all 0.2s ease; border: 1px solid transparent; border-bottom: none; }
      .tab-item:hover { background: rgba(214,139,22,0.1); color: #800020; }
      .tab-item.active { background: linear-gradient(135deg, #7a4e0a, #d68b16); color: #fff; font-weight: 700; border-color: #d68b16; }

      /* TABLES */
      .jewel-table { border-collapse: collapse; width: 100%; }
      .jewel-table thead tr { background: linear-gradient(135deg, #7a4e0a, #d68b16); }
      .jewel-table thead th { color: #fff; font-size: 11px; padding: 12px 10px; text-align: left; font-weight: normal; }
      .jewel-table tbody tr { border-bottom: 1px solid rgba(181,115,14,0.12); }
      .jewel-table tbody tr:hover { background: rgba(214,139,22,0.06); }

      .form-input { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid rgba(181,115,14,0.3); background: #fff; color: #3a1f00; outline: none; transition: all 0.2s ease; font-size: 13px; }
      .form-input:focus { border-color: #d68b16; box-shadow: 0 0 0 3px rgba(214,139,22,0.15); }

      /* NORMAL WEIGHT VALUES */
      .val-normal {
          font-weight: normal !important;
          font-family: 'Poppins', sans-serif !important;
      }

      @media (max-width: 768px) {
          .sidebar { transform: translateX(-100%); }
          .sidebar.open { transform: translateX(0); }
          .page-wrapper { margin-left: 0 !important; }
          nav.nav-gold { margin-left: 0 !important; }
          .mobile-burger { display: block !important; }
      }
      @media (min-width: 769px) { .mobile-burger { display: none !important; } }
  </style>
</head>
<body class="light-theme" style="background:#F5F5F5;">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-logo">
        <?php
        $logo_found = false;
        foreach($logo_paths as $path) {
            if(file_exists($path)) {
                echo '<img src="'.$path.'" alt="Logo">';
                $logo_found = true; break;
            }
        }
        if(!$logo_found) echo '<i class="fas fa-gem" style="color:#fff;font-size:30px;flex-shrink:0;"></i>';
        ?>
        <div class="sidebar-logo-text">
            <h2>MOTI JEWELLERS</h2>
            <p>Premium Since 2026</p>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main Menu</div>

        <a href="index.php">
            <i class="fas fa-home"></i> HOME
        </a>
        <a href="billing.php">
            <i class="fas fa-receipt"></i> BILLING
        </a>
        <a href="stock.php">
            <i class="fas fa-boxes"></i> STOCK
        </a>
        <a href="customers.php">
            <i class="fas fa-users"></i> CUSTOMERS
        </a>
        <a href="sanchari_dashboard.php" class="active">
            <i class="fas fa-piggy-bank"></i> SANCHAY SCHEME
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Analytics</div>

        <a href="reports.php">
            <i class="fas fa-chart-bar"></i> REPORTS
        </a>
        <a href="due_list.php">
            <i class="fas fa-hourglass-half"></i> DUE LIST
        </a>
        <a href="income_expenses.php">
            <i class="fas fa-chart-line"></i> INCOME & EXP
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Tools</div>

        <a href="whatsapp_automation.php">
            <i class="fab fa-whatsapp"></i> WHATSAPP
        </a>
        <a href="purchase.php">
            <i class="fas fa-book"></i> PURCHASE
        </a>
        <a href="contacts.php">
            <i class="fas fa-address-book"></i> CONTACTS
        </a>
        <a href="accounts.php">
            <i class="fas fa-calculator"></i> ACCOUNTS
        </a>
    </nav>
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <i class="fas fa-user-circle"></i>
            <div class="user-details">
                <p><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                <span><?php echo htmlspecialchars($_SESSION['user_mobile'] ?? 'Admin'); ?></span>
            </div>
        </div>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
    </div>
</div>

<!-- TOP NAVBAR -->
<nav class="nav-gold shadow-lg sticky top-0 z-50" style="margin-left:240px;">
    <div class="container mx-auto px-4 sm:px-6 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="mobile-burger" style="display:none;">
                    <div class="burger-menu" id="burgerMenu" onclick="toggleSidebar()">
                        <span></span><span></span><span></span>
                    </div>
                </div>
                <span class="font-bold text-white text-xs sm:text-sm" style="font-family:'Playfair Display',serif;">
                    <i class="fas fa-piggy-bank mr-2"></i>MOTI JEWELLERS - Swarna Sanchay Scheme
                </span>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="sanchari_register.php" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1.5 rounded-lg transition-all font-semibold hidden sm:inline-flex">
                    <i class="fas fa-user-plus mr-1"></i> Register
                </a>
                <a href="sanchari_payment.php" class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1.5 rounded-lg transition-all font-semibold shadow hidden sm:inline-flex">
                    <i class="fas fa-coins mr-1"></i> Payment
                </a>
                <span class="text-xs sm:text-sm font-medium text-white hidden md:inline">
                    <i class="fas fa-user mr-1"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </span>
            </div>
        </div>
    </div>
</nav>

<!-- PAGE WRAPPER -->
<div class="page-wrapper">
<div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">

    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold" style="color:#800020;font-family:'Playfair Display',serif;">
                 Swarna Sanchay Gold Scheme
            </h2>
            <p class="text-xs sm:text-sm mt-1" style="color:#7a4e0a;">Manage advance gold bookings, monthly installments, passbooks, and redemptions</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:gap-3 w-full md:w-auto">
            <a href="sanchari_register.php" class="btn-jewel flex-1 md:flex-initial text-center justify-center"><i class="fas fa-user-plus"></i> NEW CUSTOMER</a>
            <a href="sanchari_payment.php" class="btn-gold flex-1 md:flex-initial text-center justify-center"><i class="fas fa-coins"></i> PAYMENT ENTRY</a>
            <a href="sanchari_redemption.php" class="btn-jewel flex-1 md:flex-initial text-center justify-center" style="background:linear-gradient(135deg,#011921,#03373b);"><i class="fas fa-gem"></i> REDEMPTION</a>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if(isset($_GET['msg'])): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm text-xs sm:text-sm" style="background:#f0fdf4;border:1px solid #86efac;color:#166534;">
        <div class="flex items-center gap-2 font-semibold">
            <i class="fas fa-check-circle text-lg"></i>
            <?= $_GET['msg']==='updated' ? 'Record updated successfully!' : ($_GET['msg']==='registered' ? 'New customer registered successfully!' : ($_GET['msg']==='deleted' ? 'Customer deleted successfully!' : 'Payment recorded successfully!')) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- STATS CARDS GRID -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-8">
        <div class="stat-gem">
            <i class="fas fa-users text-xl sm:text-2xl mb-1 sm:mb-2 text-yellow-900"></i>
            <h3 class="text-lg sm:text-xl val-normal" style="color:#800020;"><?=$total_customers?></h3>
            <p class="text-[9px] sm:text-[10px] uppercase" style="color:#7a4e0a;">Total Customers</p>
        </div>
        <div class="stat-gem">
            <i class="fas fa-user-check text-xl sm:text-2xl mb-1 sm:mb-2 text-green-600"></i>
            <h3 class="text-lg sm:text-xl val-normal text-green-800"><?=$active_customers?></h3>
            <p class="text-[9px] sm:text-[10px] uppercase" style="color:#7a4e0a;">Active Schemes</p>
        </div>
        <div class="stat-gem">
            <i class="fas fa-coins text-xl sm:text-2xl mb-1 sm:mb-2 text-yellow-600"></i>
            <h3 class="text-lg sm:text-xl val-normal" style="color:#800020;">₹<?=number_format($total_collected,0)?></h3>
            <p class="text-[9px] sm:text-[10px] uppercase" style="color:#7a4e0a;">Total Collection</p>
        </div>
        <div class="stat-gem">
            <i class="fas fa-calendar-alt text-xl sm:text-2xl mb-1 sm:mb-2 text-blue-600"></i>
            <h3 class="text-lg sm:text-xl val-normal text-blue-900">₹<?=number_format($this_month,0)?></h3>
            <p class="text-[9px] sm:text-[10px] uppercase" style="color:#7a4e0a;">This Month</p>
        </div>
        <div class="stat-gem">
            <i class="fas fa-gem text-xl sm:text-2xl mb-1 sm:mb-2 text-purple-600"></i>
            <h3 class="text-lg sm:text-xl val-normal text-purple-900"><?=number_format($total_weight,3)?> g</h3>
            <p class="text-[9px] sm:text-[10px] uppercase" style="color:#7a4e0a;">Gold Accumulated</p>
        </div>
        <div class="stat-gem" style="border-color:#fca5a5 !important;">
            <i class="fas fa-exclamation-circle text-xl sm:text-2xl mb-1 sm:mb-2 text-red-600"></i>
            <h3 class="text-lg sm:text-xl val-normal text-red-700">₹<?=number_format($pending_balance,0)?></h3>
            <p class="text-[9px] sm:text-[10px] uppercase text-red-600">Pending Dues</p>
        </div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="nav-tab-gold">
        <a class="tab-item <?=$tab==='overview'?'active':''?>" href="?tab=overview"><i class="fas fa-chart-line mr-1.5"></i>Overview</a>
        <a class="tab-item <?=$tab==='customers'?'active':''?>" href="?tab=customers"><i class="fas fa-users mr-1.5"></i>Customers</a>
        <a class="tab-item <?=$tab==='payments'?'active':''?>" href="?tab=payments"><i class="fas fa-receipt mr-1.5"></i>Payments</a>
        <a class="tab-item <?=$tab==='update'?'active':''?>" href="?tab=update"><i class="fas fa-edit mr-1.5"></i>Update</a>
        <a class="tab-item <?=$tab==='reports'?'active':''?>" href="?tab=reports"><i class="fas fa-file-alt mr-1.5"></i>Reports</a>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <?php if($tab==='overview'): ?>
    <div class="jewel-card p-4 sm:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base sm:text-lg font-bold" style="color:#800020;">
                <i class="fas fa-history mr-2" style="color:#d68b16;"></i> Recent Installment Payments
            </h3>
            <a href="sanchari_payment.php" class="btn-gold text-xs px-3 py-1.5"><i class="fas fa-plus"></i> New Entry</a>
        </div>

        <div class="overflow-x-auto">
            <table class="jewel-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Customer Name</th>
                        <th>Date</th>
                        <th>Amount (₹)</th>
                        <th>Gold Weight</th>
                        <th>Mode</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = mysqli_query($conn,"SELECT * FROM sanchari_payments ORDER BY id DESC LIMIT 15");
                    if($recent && mysqli_num_rows($recent) > 0):
                        while($r=mysqli_fetch_assoc($recent)):
                    ?>
                    <tr>
                        <td class="px-3 py-2.5 text-xs val-normal text-yellow-900"><?=htmlspecialchars($r['payment_id'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($r['customer_name'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($r['payment_date'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-green-700">₹<?=number_format($r['amount'],2)?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-yellow-900"><?=number_format($r['weight'],3)?> g</td>
                        <td class="px-3 py-2.5 text-xs"><span class="px-2 py-0.5 rounded text-[10px] bg-yellow-100 text-yellow-900 val-normal"><?=htmlspecialchars($r['payment_mode'])?></span></td>
                        <td class="px-3 py-2.5 text-xs text-center">
                            <a href="?pdf=1&cid=<?=urlencode($r['customer_id'])?>" target="_blank" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-[11px] transition-all val-normal">
                                <i class="fas fa-file-pdf mr-1"></i> Statement
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr><td colspan="7" class="text-center py-6 text-gray-500">No recent payments recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: CUSTOMERS -->
    <?php elseif($tab==='customers'): ?>
    <div class="jewel-card p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <h3 class="text-base sm:text-lg font-bold" style="color:#800020;">
                <i class="fas fa-users mr-2" style="color:#d68b16;"></i> Sanchay Customers Directory
            </h3>
            <a href="sanchari_register.php" class="btn-jewel text-xs px-3 py-1.5"><i class="fas fa-user-plus"></i> Register Customer</a>
        </div>

        <div class="overflow-x-auto">
            <table class="jewel-table">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Book ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Monthly (₹)</th>
                        <th>Total Paid (₹)</th>
                        <th>Installments</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($customers_data && mysqli_num_rows($customers_data) > 0): ?>
                    <?php while($c=mysqli_fetch_assoc($customers_data)): ?>
                    <tr>
                        <td class="px-3 py-2.5 text-xs val-normal text-yellow-900"><?=htmlspecialchars($c['customer_id'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal font-mono"><?=htmlspecialchars($c['book_id'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($c['customer_name'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($c['mobile'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal">₹<?=number_format($c['monthly_amount'],2)?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-green-700">₹<?=number_format($c['total_paid'],2)?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-center"><?=htmlspecialchars($c['installments'])?></td>
                        <td class="px-3 py-2.5 text-xs"><span class="px-2 py-0.5 rounded text-[10px] bg-green-100 text-green-800 val-normal"><?=htmlspecialchars($c['status'])?></span></td>
                        <td class="px-3 py-2.5 text-xs text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="?pdf=1&cid=<?=urlencode($c['customer_id'])?>" target="_blank" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-[11px] val-normal">
                                    <i class="fas fa-file-pdf"></i> Statement
                                </a>
                                <form method="post" action="sanchari_dashboard.php?tab=customers" onsubmit="return confirm('Are you sure you want to delete customer <?=htmlspecialchars(addslashes($c['customer_name']))?>? This will delete all payments and redemptions.');" style="display:inline-block;">
                                    <input type="hidden" name="delete_customer" value="1">
                                    <input type="hidden" name="customer_id" value="<?=htmlspecialchars($c['customer_id'])?>">
                                    <button type="submit" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-[11px] transition-all val-normal">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr><td colspan="9" class="text-center py-6 text-gray-500">No customers registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: PAYMENTS -->
    <?php elseif($tab==='payments'): ?>
    <div class="jewel-card p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
            <i class="fas fa-receipt mr-2" style="color:#d68b16;"></i> Complete Installment Payment Logs
        </h3>

        <div class="overflow-x-auto">
            <table class="jewel-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Installment #</th>
                        <th>Date</th>
                        <th>Amount (₹)</th>
                        <th>Gold Rate</th>
                        <th>Weight (g)</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payments_data && mysqli_num_rows($payments_data) > 0): ?>
                    <?php while($p=mysqli_fetch_assoc($payments_data)): ?>
                    <tr>
                        <td class="px-3 py-2.5 text-xs val-normal text-yellow-900"><?=htmlspecialchars($p['payment_id'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal font-mono"><?=htmlspecialchars($p['customer_id'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($p['customer_name'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-center"><?=htmlspecialchars($p['installment_no'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal"><?=htmlspecialchars($p['payment_date'])?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-green-700">₹<?=number_format($p['amount'],2)?></td>
                        <td class="px-3 py-2.5 text-xs val-normal">₹<?=number_format($p['gold_rate'],2)?></td>
                        <td class="px-3 py-2.5 text-xs val-normal text-yellow-900"><?=number_format($p['weight'],3)?> g</td>
                        <td class="px-3 py-2.5 text-xs"><span class="px-2 py-0.5 rounded text-[10px] bg-yellow-100 text-yellow-900 val-normal"><?=htmlspecialchars($p['payment_mode'])?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr><td colspan="9" class="text-center py-6 text-gray-500">No payment records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: UPDATE -->
    <?php elseif($tab==='update'): ?>
    <div class="jewel-card p-4 sm:p-6 max-w-2xl mx-auto">
        <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
            <i class="fas fa-edit mr-2" style="color:#d68b16;"></i> Update Payment &amp; Status
        </h3>

        <form method="post" action="sanchari_dashboard.php">
            <input type="hidden" name="update_payment" value="1">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Payment Entry ID</label>
                    <input type="number" name="inv_id" class="form-input" placeholder="Enter Internal Payment ID" required>
                </div>
                <div>
                    <label class="form-label">Paid Amount (₹)</label>
                    <input type="number" step="0.01" name="paid_amount" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Payment Mode</label>
                    <select name="payment_mode" class="form-input">
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Bank">Bank Transfer</option>
                        <option value="Card">Card</option>
                    </select>
                </div>
                <div class="pt-2 text-right">
                    <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TAB 5: REPORTS -->
    <?php elseif($tab==='reports'): ?>
    <div class="jewel-card p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
            <i class="fas fa-file-alt mr-2" style="color:#d68b16;"></i> Sanchay Scheme Audit &amp; Status Report
        </h3>
        <p class="text-xs sm:text-sm text-gray-700 mb-4">Click below to open full reports and update customer account statuses.</p>
        <a href="sanchari_reports.php" class="btn-jewel"><i class="fas fa-external-link-alt"></i> Open Full Reports Page</a>
    </div>
    <?php endif; ?>

</div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const burger  = document.getElementById('burgerMenu');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    if(burger) burger.classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('mainSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
    const burger  = document.getElementById('burgerMenu');
    if(burger) burger.classList.remove('active');
}
</script>
</body>
</html>