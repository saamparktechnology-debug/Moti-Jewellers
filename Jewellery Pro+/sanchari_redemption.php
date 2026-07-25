<?php
session_start();
require_once 'config/database.php';
if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$logo_paths = ['logo.png', 'assets/images/logo.png', 'images/logo.png', 'assets/images/moti-removebg-preview.png'];

// -- Generate Redemption ID --------------------------------------------
function genRedemptionId($conn) {
    $res = mysqli_query($conn, "SELECT redemption_id FROM sanchari_redemptions ORDER BY id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    $num = 1;
    if($row && $row['redemption_id']) {
        $num = (int)substr($row['redemption_id'], 3) + 1;
    }
    return 'RED' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// -- Handle Form Submit ------------------------------------------------
$success = false;
$saved_id = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_redemption'])) {
    $redemption_id   = genRedemptionId($conn);
    $customer_id     = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
    $book_id         = mysqli_real_escape_string($conn, trim($_POST['book_id']));
    $purchase_date   = mysqli_real_escape_string($conn, trim($_POST['purchase_date']));
    $item_name       = mysqli_real_escape_string($conn, trim($_POST['item_name']));
    $gross_weight    = (float)$_POST['gross_weight'];
    $net_weight      = (float)$_POST['net_weight'];
    $making_charge   = (float)$_POST['making_charge'];
    $gst             = (float)$_POST['gst'];
    $jewellery_amount= (float)$_POST['jewellery_amount'];
    $adjusted_amount = (float)$_POST['adjusted_amount'];
    $balance_amount  = (float)$_POST['balance_amount'];

    $stmt = mysqli_prepare($conn, "INSERT INTO sanchari_redemptions (redemption_id, customer_id, book_id, purchase_date, item_name, gross_weight, net_weight, making_charge, gst, jewellery_amount, adjusted_amount, balance_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssddddddd', $redemption_id, $customer_id, $book_id, $purchase_date, $item_name, $gross_weight, $net_weight, $making_charge, $gst, $jewellery_amount, $adjusted_amount, $balance_amount);
    mysqli_stmt_execute($stmt);

    // Update customer status to Completed
    mysqli_query($conn, "UPDATE sanchari_customers SET status='Completed' WHERE customer_id='$customer_id'");

    $success = true;
    $saved_id = $redemption_id;
}

// -- Handle PDF Receipt ------------------------------------------------
if(isset($_GET['pdf']) && isset($_GET['rid'])) {
    $rid = mysqli_real_escape_string($conn, $_GET['rid']);
    $r   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sr.*, sc.customer_name, sc.mobile, sc.address, sc.monthly_amount, sc.scheme_duration, sc.joining_date FROM sanchari_redemptions sr LEFT JOIN sanchari_customers sc ON sr.customer_id=sc.customer_id WHERE sr.redemption_id='$rid'"));
    if(!$r) { echo "Record not found"; exit; }
    // Get payment summary
    $pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) total_paid, COALESCE(SUM(weight),0) total_weight, COUNT(*) installments FROM sanchari_payments WHERE customer_id='".$r['customer_id']."'"));
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Redemption Receipt - <?= htmlspecialchars($rid) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; padding: 30px; }
  .header { text-align: center; border-bottom: 3px solid #800020; padding-bottom: 14px; margin-bottom: 18px; }
  .header h1 { color: #800020; font-size: 22px; letter-spacing: 1px; }
  .header p { color: #666; font-size: 11px; margin-top: 4px; }
  .badge { display: inline-block; background: #800020; color: #fff; padding: 3px 12px; border-radius: 20px; font-size: 11px; margin-top: 6px; }
  .section { margin-bottom: 16px; }
  .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #800020; border-bottom: 1px solid #f0ddd5; padding-bottom: 4px; margin-bottom: 8px; font-weight: bold; }
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; }
  .field { margin-bottom: 4px; }
  .field .lbl { color: #888; font-size: 10px; text-transform: uppercase; }
  .field .val { font-weight: bold; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th { background: #800020; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; }
  td { padding: 7px 10px; border-bottom: 1px solid #f0e8e0; }
  tr:nth-child(even) td { background: #fdf6f0; }
  .highlight { background: #fff8f4; border: 2px solid #800020; border-radius: 8px; padding: 12px 16px; margin-top: 16px; }
  .highlight .big { font-size: 20px; font-weight: bold; color: #800020; }
  .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 12px; }
  .sign-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
  .sign-box { text-align: center; border-top: 1px solid #ccc; padding-top: 6px; font-size: 10px; color: #888; }
  @media print { body { padding: 15px; } }
</style>
</head>
<body>
<div class="header">
  <h1> Moti Jewellers</h1>
  <p>Sanchari Scheme - Redemption Receipt</p>
  <span class="badge"><?= htmlspecialchars($r['redemption_id']) ?></span>
</div>

<div class="section">
  <div class="section-title">Customer Details</div>
  <div class="grid2">
    <div class="field"><div class="lbl">Customer Name</div><div class="val"><?= htmlspecialchars($r['customer_name']) ?></div></div>
    <div class="field"><div class="lbl">Customer ID</div><div class="val"><?= htmlspecialchars($r['customer_id']) ?></div></div>
    <div class="field"><div class="lbl">Book ID</div><div class="val"><?= htmlspecialchars($r['book_id']) ?></div></div>
    <div class="field"><div class="lbl">Mobile</div><div class="val"><?= htmlspecialchars($r['mobile']) ?></div></div>
    <div class="field"><div class="lbl">Joining Date</div><div class="val"><?= htmlspecialchars($r['joining_date']) ?></div></div>
    <div class="field"><div class="lbl">Scheme</div><div class="val">₹<?= number_format($r['monthly_amount'],2) ?>/mo  <?= htmlspecialchars($r['scheme_duration']) ?></div></div>
  </div>
</div>

<div class="section">
  <div class="section-title">Scheme Summary</div>
  <table>
    <thead><tr><th>Total Installments</th><th>Total Amount Paid</th><th>Gold Accumulated</th></tr></thead>
    <tbody>
      <tr><td><?= $pay['installments'] ?></td><td>₹<?= number_format($pay['total_paid'],2) ?></td><td><?= number_format($pay['total_weight'],3) ?>g</td></tr>
    </tbody>
  </table>
</div>

<div class="section">
  <div class="section-title">Jewellery Purchase Details</div>
  <div class="grid2" style="margin-bottom:10px">
    <div class="field"><div class="lbl">Item Name</div><div class="val"><?= htmlspecialchars($r['item_name']) ?></div></div>
    <div class="field"><div class="lbl">Purchase Date</div><div class="val"><?= htmlspecialchars($r['purchase_date']) ?></div></div>
  </div>
  <table>
    <thead><tr><th>Gross Weight</th><th>Net Weight</th><th>Making Charge</th><th>GST</th><th>Jewellery Amount</th></tr></thead>
    <tbody>
      <tr>
        <td><?= number_format($r['gross_weight'],3) ?>g</td>
        <td><?= number_format($r['net_weight'],3) ?>g</td>
        <td>₹<?= number_format($r['making_charge'],2) ?></td>
        <td>₹<?= number_format($r['gst'],2) ?></td>
        <td><strong>₹<?= number_format($r['jewellery_amount'],2) ?></strong></td>
      </tr>
    </tbody>
  </table>
</div>

<div class="highlight">
  <div class="grid2">
    <div>
      <div style="font-size:11px;color:#888;text-transform:uppercase">Jewellery Amount</div>
      <div class="big">₹<?= number_format($r['jewellery_amount'],2) ?></div>
    </div>
    <div>
      <div style="font-size:11px;color:#888;text-transform:uppercase">Scheme Adjusted</div>
      <div class="big" style="color:#27ae60">- ₹<?= number_format($r['adjusted_amount'],2) ?></div>
    </div>
  </div>
  <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e0c4b0">
    <div style="font-size:11px;color:#888;text-transform:uppercase">Balance to Pay / Refund</div>
    <div style="font-size:24px;font-weight:bold;color:<?= $r['balance_amount'] >= 0 ? '#800020' : '#27ae60' ?>">
      <?= $r['balance_amount'] >= 0 ? '₹'.number_format($r['balance_amount'],2).' (Customer Pays)' : '₹'.number_format(abs($r['balance_amount']),2).' (Refund to Customer)' ?>
    </div>
  </div>
</div>

<div class="sign-row">
  <div class="sign-box">Customer Signature</div>
  <div class="sign-box">Authorized Signatory<br>Moti Jewellers</div>
</div>

<div class="footer">
  Generated on <?= date('d M Y H:i') ?> | Moti Jewellers - Sanchay Scheme
</div>

<script>window.onload=()=>window.print();</script>
</body>
</html>
<?php
    exit;
}

// -- Load customers for dropdown ----------------------------------------
$customers = mysqli_query($conn, "SELECT sc.*, COALESCE(SUM(sp.amount),0) total_paid, COALESCE(SUM(sp.weight),0) total_weight, COUNT(sp.id) installments FROM sanchari_customers sc LEFT JOIN sanchari_payments sp ON sc.customer_id=sp.customer_id GROUP BY sc.id ORDER BY sc.customer_name ASC");
$cust_list = [];
while($r = mysqli_fetch_assoc($customers)) $cust_list[] = $r;

// -- Past redemptions --------------------------------------------------
$past = mysqli_query($conn, "SELECT sr.*, sc.customer_name, sc.mobile FROM sanchari_redemptions sr LEFT JOIN sanchari_customers sc ON sr.customer_id=sc.customer_id ORDER BY sr.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>Sanchay Gold Redemption - MOTI JEWELLERS</title>
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
      .form-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1.5px solid rgba(181,115,14,0.3); background: #fff; color: #3a1f00; outline: none; transition: all 0.2s ease; font-size: 13px; }
      .form-input:focus { border-color: #d68b16; box-shadow: 0 0 0 4px rgba(214,139,22,0.15); }
      .form-label { display: block; color: #7a4e0a; font-weight: 600; font-size: 12px; margin-bottom: 6px; }
      .required { color: #dc2626; }

      .btn-gold { background: linear-gradient(135deg, #800020, #d68b16); border: none; color: #fff; font-weight: 700; padding: 12px 28px; border-radius: 50px; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; text-decoration: none; }
      .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(214,139,22,0.4); color: #fff; }

      .customer-card { background: #fff; border: 1.5px solid #d68b16; border-radius: 14px; padding: 16px; margin-bottom: 20px; display: none; }
      .customer-card.show { display: block; }
      .stat-pill { background: #fdf6e3; border: 1px solid #e5c98a; border-radius: 10px; padding: 10px; text-align: center; }

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
                    <i class="fas fa-gem mr-2"></i>MOTI JEWELLERS - Sanchay Gold Redemption
                </span>
            </div>
            <div class="flex items-center gap-3">
                <a href="sanchari_dashboard.php" class="text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg transition-all font-semibold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- PAGE WRAPPER -->
<div class="page-wrapper">
<div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold" style="color:#800020;font-family:'Playfair Display',serif;">
                 Sanchay Gold Scheme Redemption
            </h2>
            <p class="text-xs sm:text-sm mt-1" style="color:#7a4e0a;">Maturity settlement, jewellery purchase adjustment, and receipt printing</p>
        </div>
    </div>

    <?php if($success): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center justify-between text-xs sm:text-sm" style="background:#f0fdf4;border:1px solid #86efac;color:#166534;">
        <div>
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <strong>Redemption Saved Successfully!</strong> Redemption ID: <strong><?= $saved_id ?></strong>
        </div>
        <a href="?pdf=1&rid=<?= urlencode($saved_id) ?>" target="_blank" class="btn-gold text-xs px-4 py-2">
            <i class="fas fa-print"></i> Print Official Receipt
        </a>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">

        <!-- LEFT: Form -->
        <div class="lg:col-span-7">
            <div class="jewel-card p-4 sm:p-8">
                <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
                    <i class="fas fa-edit mr-2" style="color:#d68b16;"></i> New Redemption Settlement Entry
                </h3>

                <!-- Customer Select -->
                <div class="mb-4">
                    <label class="form-label">Select Customer <span class="required">*</span></label>
                    <select id="custSelect" class="form-input font-semibold">
                        <option value="">-- Choose Registered Customer --</option>
                        <?php foreach($cust_list as $c): ?>
                        <option value="<?= htmlspecialchars($c['customer_id']) ?>"
                            data-book="<?= htmlspecialchars($c['book_id']) ?>"
                            data-name="<?= htmlspecialchars($c['customer_name']) ?>"
                            data-mobile="<?= htmlspecialchars($c['mobile']) ?>"
                            data-monthly="<?= $c['monthly_amount'] ?>"
                            data-scheme="<?= htmlspecialchars($c['scheme_duration']) ?>"
                            data-joined="<?= htmlspecialchars($c['joining_date']) ?>"
                            data-paid="<?= $c['total_paid'] ?>"
                            data-weight="<?= $c['total_weight'] ?>"
                            data-installments="<?= $c['installments'] ?>"
                            data-status="<?= htmlspecialchars($c['status']) ?>">
                            <?= htmlspecialchars($c['customer_id']) ?> - <?= htmlspecialchars($c['customer_name']) ?> (<?= htmlspecialchars($c['book_id']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Customer Info Card -->
                <div class="customer-card" id="custCard">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div><small class="text-gray-500 text-xs">Customer Name</small><div class="font-bold text-sm text-yellow-900" id="ci_name"></div></div>
                        <div><small class="text-gray-500 text-xs">Mobile Number</small><div class="font-semibold text-sm" id="ci_mobile"></div></div>
                        <div><small class="text-gray-500 text-xs">Book ID</small><div class="font-bold text-sm text-yellow-900" id="ci_book"></div></div>
                        <div><small class="text-gray-500 text-xs">Status</small><div><span id="ci_status" class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800"></span></div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="stat-pill">
                            <div class="text-base sm:text-lg font-bold text-yellow-900" id="ci_installments">0</div>
                            <div class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-semibold">Installments</div>
                        </div>
                        <div class="stat-pill">
                            <div class="text-base sm:text-lg font-bold text-green-700" id="ci_paid">₹0</div>
                            <div class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-semibold">Total Paid</div>
                        </div>
                        <div class="stat-pill">
                            <div class="text-base sm:text-lg font-bold text-yellow-900" id="ci_weight">0g</div>
                            <div class="text-[9px] sm:text-[10px] text-gray-500 uppercase font-semibold">Gold Weight</div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form method="post" id="redemptionForm">
                    <input type="hidden" name="save_redemption" value="1">
                    <input type="hidden" name="customer_id" id="f_customer_id">
                    <input type="hidden" name="book_id" id="f_book_id">

                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider mb-3" style="color:#7a4e0a;border-bottom:1px solid rgba(181,115,14,0.2);padding-bottom:4px;">
                        <i class="fas fa-gem mr-1"></i> Jewellery Purchase Details
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <div class="md:col-span-2">
                            <label class="form-label">Item Name <span class="required">*</span></label>
                            <input type="text" name="item_name" class="form-input" placeholder="e.g. Gold Necklace 22KT 916" required>
                        </div>
                        <div>
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Gross Weight (g)</label>
                            <input type="number" step="0.001" name="gross_weight" id="f_gross" class="form-input" value="0" required>
                        </div>
                        <div>
                            <label class="form-label">Net Weight (g)</label>
                            <input type="number" step="0.001" name="net_weight" id="f_net" class="form-input" value="0" required>
                        </div>
                        <div>
                            <label class="form-label">Making Charge (₹)</label>
                            <input type="number" step="0.01" name="making_charge" id="f_making" class="form-input" value="0">
                        </div>
                        <div>
                            <label class="form-label">GST (₹)</label>
                            <input type="number" step="0.01" name="gst" id="f_gst" class="form-input" value="0">
                        </div>
                        <div>
                            <label class="form-label">Jewellery Total Amount (₹)</label>
                            <input type="number" step="0.01" name="jewellery_amount" id="f_jewellery" class="form-input font-bold text-yellow-900" value="0">
                        </div>
                    </div>

                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider mt-6 mb-3" style="color:#7a4e0a;border-bottom:1px solid rgba(181,115,14,0.2);padding-bottom:4px;">
                        <i class="fas fa-calculator mr-1"></i> Settlement Calculation
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="form-label">Scheme Deposit Adjusted (₹)</label>
                            <input type="number" step="0.01" name="adjusted_amount" id="f_adjusted" class="form-input font-bold text-green-700" value="0" readonly style="background:#fff9ed;">
                        </div>
                        <div>
                            <label class="form-label">Net Balance Amount (₹)</label>
                            <input type="number" step="0.01" name="balance_amount" id="f_balance" class="form-input font-bold text-red-700" value="0" readonly style="background:#fff9ed;">
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                        <button type="button" class="px-5 py-2.5 rounded-full text-xs font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 text-center" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn-gold justify-center" id="submitBtn" disabled><i class="fas fa-check-circle"></i> Save Redemption Entry</button>
                    </div>
                </form>

            </div>
        </div>

        <!-- RIGHT: Past Redemptions -->
        <div class="lg:col-span-5">
            <div class="jewel-card p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-bold mb-4" style="color:#800020;">
                    <i class="fas fa-history mr-2" style="color:#d68b16;"></i> Past Redemption Logs
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr style="background:linear-gradient(135deg, #7a4e0a, #d68b16);color:#fff;">
                                <th class="p-2">ID</th>
                                <th class="p-2">Customer</th>
                                <th class="p-2">Item</th>
                                <th class="p-2">Net Amt</th>
                                <th class="p-2 text-center">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($past && mysqli_num_rows($past) > 0): ?>
                            <?php while($r=mysqli_fetch_assoc($past)): ?>
                            <tr class="border-b border-yellow-900/10 hover:bg-yellow-900/5">
                                <td class="p-2 font-bold text-yellow-900"><?= htmlspecialchars($r['redemption_id']) ?></td>
                                <td class="p-2">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($r['customer_name']) ?></div>
                                    <span class="text-[10px] text-gray-500"><?= htmlspecialchars($r['customer_id']) ?></span>
                                </td>
                                <td class="p-2"><?= htmlspecialchars($r['item_name']) ?></td>
                                <td class="p-2 font-bold text-green-700">₹<?= number_format($r['jewellery_amount'],0) ?></td>
                                <td class="p-2 text-center">
                                    <a href="?pdf=1&rid=<?= urlencode($r['redemption_id']) ?>" target="_blank" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded font-semibold transition-all">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">No redemption records found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
</div>

<script>
const sel = document.getElementById('custSelect');
const custCard = document.getElementById('custCard');
let currentPaid = 0;

sel.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if(!opt.value) { custCard.classList.remove('show'); document.getElementById('submitBtn').disabled=true; return; }

  document.getElementById('f_customer_id').value = opt.value;
  document.getElementById('f_book_id').value = opt.dataset.book;

  document.getElementById('ci_name').textContent = opt.dataset.name;
  document.getElementById('ci_mobile').textContent = opt.dataset.mobile;
  document.getElementById('ci_book').textContent = opt.dataset.book;
  const statusEl = document.getElementById('ci_status');
  statusEl.textContent = opt.dataset.status;
  document.getElementById('ci_installments').textContent = opt.dataset.installments;
  document.getElementById('ci_paid').textContent = '₹' + parseFloat(opt.dataset.paid).toLocaleString('en-IN', {maximumFractionDigits:0});
  document.getElementById('ci_weight').textContent = parseFloat(opt.dataset.weight).toFixed(3) + 'g';

  currentPaid = parseFloat(opt.dataset.paid) || 0;
  document.getElementById('f_adjusted').value = currentPaid.toFixed(2);

  custCard.classList.add('show');
  document.getElementById('submitBtn').disabled = false;
  calcBalance();
});

function calcBalance() {
  const jewellery = parseFloat(document.getElementById('f_jewellery').value) || 0;
  const adjusted  = parseFloat(document.getElementById('f_adjusted').value) || 0;
  const balance   = jewellery - adjusted;
  document.getElementById('f_balance').value = balance.toFixed(2);
}

['f_gross','f_net','f_making','f_gst','f_jewellery'].forEach(id => {
  document.getElementById(id).addEventListener('input', calcBalance);
});

function resetForm() {
  custCard.classList.remove('show');
  sel.value = '';
  currentPaid = 0;
  document.getElementById('submitBtn').disabled = true;
}

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
