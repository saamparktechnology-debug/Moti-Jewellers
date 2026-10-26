<?php
session_start();
require_once 'config/database.php';
if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$logo_paths = ['logo.png', 'assets/images/logo.png', 'images/logo.png', 'assets/images/moti-removebg-preview.png'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $customer_id = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    if ($customer_id !== '' && in_array($status, ['Active', 'Completed', 'Closed'], true)) {
        mysqli_query($conn, "UPDATE sanchari_customers SET status='" . $status . "' WHERE customer_id='" . $customer_id . "'");
    }
}

// Handle customer delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $customer_id = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
    if ($customer_id !== '') {
        mysqli_query($conn, "DELETE FROM sanchari_payments WHERE customer_id='$customer_id'");
        mysqli_query($conn, "DELETE FROM sanchari_redemptions WHERE customer_id='$customer_id'");
        mysqli_query($conn, "DELETE FROM sanchari_customers WHERE customer_id='$customer_id'");
    }
    header('Location: sanchari_reports.php?msg=deleted');
    exit;
}

$customers = mysqli_query($conn, "SELECT * FROM sanchari_customers ORDER BY id DESC");
$payments = mysqli_query($conn, "SELECT * FROM sanchari_payments ORDER BY id DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>Sanchay Scheme Reports - MOTI JEWELLERS</title>
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

      .jewel-card { background: linear-gradient(145deg, #fdf6e3, #f5ead0); border-radius: 20px; border: 1px solid rgba(181,115,14,0.25); box-shadow: 0 8px 30px rgba(181,115,14,0.1); }
      .jewel-table { border-collapse: collapse; width: 100%; }
      .jewel-table thead tr { background: linear-gradient(135deg, #7a4e0a, #d68b16); }
      .jewel-table thead th { color: #fff; font-size: 11px; padding: 10px 8px; text-align: left; }
      .jewel-table tbody tr { border-bottom: 1px solid rgba(181,115,14,0.12); }
      .jewel-table tbody tr:hover { background: rgba(214,139,22,0.05); }

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
    <link rel="stylesheet" href="mobile.css">
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
            <p>Trusted Tradition</p>
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
        <a href="transfer.php">
            <i class="fas fa-exchange-alt"></i> TRANSFER
        </a>
        <a href="product_summary.php">
            <i class="fas fa-clipboard-list"></i> PRODUCT SUMMARY
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
                    <i class="fas fa-piggy-bank mr-2"></i>MOTI JEWELLERS - Swarna Sanchay Scheme Reports
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="sanchari_dashboard.php" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1.5 rounded-lg transition-all font-semibold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="page-wrapper">
<div class="container mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h2 class="text-2xl sm:text-3xl font-bold" style="color:#800020;font-family:'Playfair Display',serif;">
             Sanchay Audit &amp; Status Reports
        </h2>
        <p class="text-xs sm:text-sm mt-1" style="color:#7a4e0a;">Complete list of registered accounts and recent transaction journals</p>
    </div>

    <!-- Toast Notification -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center justify-between shadow-sm text-xs sm:text-sm" style="background:#fdf2f2;border:1px solid #fca5a5;color:#991b1b;">
        <div class="flex items-center gap-2 font-semibold">
            <i class="fas fa-trash-alt text-lg"></i>
            Customer deleted successfully.
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Registered Customers List -->
        <div class="jewel-card p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
                <i class="fas fa-users mr-2" style="color:#d68b16;"></i> Customer Scheme Ledger
            </h3>
            <div class="overflow-x-auto">
                <table class="jewel-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Book ID</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($customers && mysqli_num_rows($customers) > 0): ?>
                        <?php while($r=mysqli_fetch_assoc($customers)): ?>
                        <tr>
                            <td class="px-2 py-2 text-xs val-normal text-yellow-900"><?= htmlspecialchars($r['customer_id']) ?></td>
                            <td class="px-2 py-2 text-xs val-normal"><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td class="px-2 py-2 text-xs val-normal font-mono"><?= htmlspecialchars($r['book_id']) ?></td>
                            <td class="px-2 py-2 text-xs"><span class="px-2 py-0.5 rounded text-[10px] bg-yellow-100 text-yellow-900 val-normal"><?= htmlspecialchars($r['status']) ?></span></td>
                            <td class="px-2 py-2 text-xs">
                                <form method="post" class="flex gap-1">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($r['customer_id']) ?>">
                                    <select name="status" class="text-xs p-1 border rounded bg-white val-normal" style="min-width:80px;">
                                        <option value="Active" <?= ($r['status']=='Active'?'selected':'') ?>>Active</option>
                                        <option value="Completed" <?= ($r['status']=='Completed'?'selected':'') ?>>Completed</option>
                                        <option value="Closed" <?= ($r['status']=='Closed'?'selected':'') ?>>Closed</option>
                                    </select>
                                    <button class="px-2 py-1 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs val-normal" type="submit">Save</button>
                                </form>
                            </td>
                            <td class="px-2 py-2 text-xs text-center">
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete customer <?=htmlspecialchars(addslashes($r['customer_name']))?>? This will delete all payments and redemptions.');" style="display:inline-block;">
                                    <input type="hidden" name="delete_customer" value="1">
                                    <input type="hidden" name="customer_id" value="<?=htmlspecialchars($r['customer_id'])?>">
                                    <button type="submit" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs val-normal">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-gray-500">No customer records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Installment Payments Log -->
        <div class="jewel-card p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
                <i class="fas fa-coins mr-2" style="color:#d68b16;"></i> Installment Payment Entries
            </h3>
            <div class="overflow-x-auto">
                <table class="jewel-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Customer Name</th>
                            <th>Amount (₹)</th>
                            <th>Gold Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($payments && mysqli_num_rows($payments) > 0): ?>
                        <?php while($r=mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td class="px-2 py-2 text-xs val-normal text-yellow-900"><?= htmlspecialchars($r['payment_id']) ?></td>
                            <td class="px-2 py-2 text-xs val-normal"><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td class="px-2 py-2 text-xs val-normal text-green-700">₹<?= number_format($r['amount'],2) ?></td>
                            <td class="px-2 py-2 text-xs val-normal text-yellow-900"><?= number_format($r['weight'],3) ?> g</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">No payment logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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