<?php
session_start();
require_once 'config/database.php';
if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$logo_paths = ['logo.png', 'assets/images/logo.png', 'images/logo.png', 'assets/images/moti-removebg-preview.png'];

function genId($prefix, $table, $field) {
  global $conn;
  $res = mysqli_query($conn, "SELECT $field FROM $table ORDER BY id DESC LIMIT 1");
  $row = mysqli_fetch_assoc($res);
  $num = 1;
  if($row && $row[$field]) {
    $num = (int)substr($row[$field], strlen($prefix)) + 1;
  }
  return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

$auto_cid = genId('CUS', 'sanchari_customers', 'customer_id');
$auto_bid = genId('SB', 'sanchari_customers', 'book_id');
$success_msg = '';
$error_msg = '';

if($_SERVER['REQUEST_METHOD']==='POST') {
  $customer_id = mysqli_real_escape_string($conn, trim($_POST['customer_id']));
  $book_id = mysqli_real_escape_string($conn, trim($_POST['book_id']));
  $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
  $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $address = mysqli_real_escape_string($conn, trim($_POST['address']));
  $joining_date = mysqli_real_escape_string($conn, trim($_POST['joining_date']));
  $monthly_amount = (float)$_POST['monthly_amount'];
  $scheme_duration = mysqli_real_escape_string($conn, trim($_POST['scheme_duration']));
  $status = mysqli_real_escape_string($conn, trim($_POST['status']));

  if($customer_name==='' || $mobile==='' || $monthly_amount<=0){ 
      $error_msg = 'Please fill in all mandatory fields.'; 
  } else {
      $stmt = mysqli_prepare($conn, "INSERT INTO sanchari_customers (customer_id, book_id, customer_name, mobile, email, address, joining_date, monthly_amount, scheme_duration, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
      mysqli_stmt_bind_param($stmt, 'ssssssssss', $customer_id, $book_id, $customer_name, $mobile, $email, $address, $joining_date, $monthly_amount, $scheme_duration, $status);
      if(mysqli_stmt_execute($stmt)) {
          header('Location: sanchari_dashboard.php?tab=customers&msg=registered');
          exit;
      } else {
          $error_msg = 'Error registering customer: ' . mysqli_error($conn);
      }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>New Customer Registration - MOTI JEWELLERS</title>
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
                    <i class="fas fa-user-plus mr-2"></i>MOTI JEWELLERS - Sanchay Customer Registration
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
<div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8 max-w-4xl">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold" style="color:#800020;font-family:'Playfair Display',serif;">
                 Sanchay Customer Registration
            </h2>
            <p class="text-xs sm:text-sm mt-1" style="color:#7a4e0a;">Register a new customer for Moti Jewellers Swarna Sanchay Scheme</p>
        </div>
    </div>

    <?php if($error_msg): ?>
        <div class="mb-4 p-4 rounded-xl text-xs sm:text-sm" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;font-weight:600;">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="jewel-card p-4 sm:p-8">
        <form method="post">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="form-label">Customer ID <span class="required">*</span></label>
                    <input name="customer_id" class="form-input font-bold text-yellow-900" value="<?php echo htmlspecialchars($auto_cid); ?>" required readonly style="background:#fff9ed;">
                </div>
                <div>
                    <label class="form-label">Book ID <span class="required">*</span></label>
                    <input name="book_id" class="form-input font-bold text-yellow-900" value="<?php echo htmlspecialchars($auto_bid); ?>" required readonly style="background:#fff9ed;">
                </div>

                <div>
                    <label class="form-label">Customer Full Name <span class="required">*</span></label>
                    <input name="customer_name" class="form-input" placeholder="e.g. Rahul Sharma" required>
                </div>
                <div>
                    <label class="form-label">Mobile Number <span class="required">*</span></label>
                    <input name="mobile" class="form-input" placeholder="e.g. 9876543210" required maxlength="15" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                </div>

                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="customer@gmail.com">
                </div>
                <div>
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div>
                    <label class="form-label">Monthly Deposit Amount (₹) <span class="required">*</span></label>
                    <input type="number" step="0.01" name="monthly_amount" class="form-input font-bold text-green-700" placeholder="e.g. 3000" required>
                </div>
                <div>
                    <label class="form-label">Scheme Duration</label>
                    <select name="scheme_duration" class="form-input">
                        <option value="11 Months">11 Months</option>
                        <option value="12 Months" selected>12 Months</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Full Address</label>
                    <textarea name="address" rows="3" class="form-input" placeholder="Enter street, city, landmark, pincode..."></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Scheme Account Status</label>
                    <select name="status" class="form-input">
                        <option value="Active" selected>Active</option>
                        <option value="Completed">Completed</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row justify-end gap-3 sm:gap-4">
                <a href="sanchari_dashboard.php" class="px-6 py-3 rounded-full text-xs font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 transition-all text-center">Cancel</a>
                <button type="submit" class="btn-gold justify-center"><i class="fas fa-check-circle"></i> Save Customer Account</button>
            </div>
        </form>
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
