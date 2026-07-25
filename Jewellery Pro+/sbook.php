<?php
session_start();
require_once 'config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$logo_paths = ['logo.png', 'assets/images/logo.png', 'images/logo.png', 'assets/images/moti-removebg-preview.png'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Sanchay Scheme Passbook - MOTI JEWELLERS</title>
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

        .btn-jewel {
            background: linear-gradient(135deg, #800020, #d68b16);
            border: none; border-radius: 50px; padding: 12px 30px;
            font-weight: 700; color: #fff; transition: all 0.3s ease;
            display: inline-block; text-decoration: none; font-size: 14px;
        }
        .btn-jewel:hover { transform: scale(1.05); box-shadow: 0 10px 30px rgba(214,139,22,0.4); color: #fff; }
        .form-card { background: #fff; border-radius: 24px; box-shadow: 0 25px 80px rgba(0,0,0,0.08); border: 1px solid rgba(214,139,22,0.12); }

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
                    <i class="fas fa-piggy-bank mr-2"></i>MOTI JEWELLERS - Swarna Sanchay Booking System
                </span>
            </div>
            <div class="flex items-center gap-3">
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
    <section class="hero-with-logo py-8 sm:py-10 relative text-center" style="background:linear-gradient(135deg, #fdf6e3 0%, #f5ead0 50%, #fdf6e3 100%);">
        <div class="container mx-auto px-4 sm:px-6 text-center">
            <div class="floating-logo mb-4">
                <?php
                $logo_found = false;
                foreach($logo_paths as $path) {
                    if(file_exists($path)) {
                        echo '<img src="'.$path.'" alt="MOTI JEWELLERS Logo" style="height:90px;width:90px;object-fit:contain;margin:0 auto;" class="sm:h-28 sm:w-28">';
                        $logo_found = true; break;
                    }
                }
                if(!$logo_found) echo '<i class="fas fa-gem" style="font-size:60px;color:#d68b16;"></i>';
                ?>
            </div>

            <h1 class="text-2xl sm:text-4xl font-bold mt-2 mb-2 sm:mb-3" style="color:#800020;font-family:'Playfair Display',serif;">
                MOTI JEWELLERS SWARNA SANCHAY
            </h1>

            <p class="text-xs sm:text-base mb-6 max-w-2xl mx-auto" style="color:#7a4e0a;">
                Gold Advance Booking / Swarna Sanchay Management System with registration, payments, passbook, redemption and reports.
            </p>

            <div class="hero-buttons flex flex-col sm:flex-row justify-center gap-3 max-w-md mx-auto sm:max-w-none">
                <a href="sanchari_register.php" class="btn-jewel text-center"><i class="fas fa-user-plus mr-2"></i> NEW CUSTOMER</a>
                <a href="sanchari_payment.php" class="btn-jewel text-center" style="background:linear-gradient(135deg,#7a4e0a,#d68b16);"><i class="fas fa-coins mr-2"></i> PAYMENT ENTRY</a>
                <a href="sanchari_dashboard.php" class="btn-jewel text-center" style="background:linear-gradient(135deg,#011921,#03373b);"><i class="fas fa-chart-line mr-2"></i> DASHBOARD</a>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="form-card p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-bold mb-3" style="color:#800020;">Swarna Sanchay Features</h3>
                <p class="text-xs sm:text-sm text-gray-600 mb-4">Complete modules for customer registration, payment entry, passbook viewing, redemption, reports and dashboard are available.</p>
                <ul class="text-xs text-gray-700 space-y-2">
                    <li><i class="fas fa-check text-green-600 mr-2"></i> Auto-generated Customer ID / Book ID / Payment ID</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i> Gold weight auto calculated from amount and rate</li>
                    <li><i class="fas fa-check text-green-600 mr-2"></i> Passbook, reports, redemption and dashboard modules</li>
                </ul>
            </div>

            <div class="form-card p-4 sm:p-6" style="background:#e8f5e9;border-color:#c8e6c9;">
                <h4 class="font-bold text-green-900 mb-4 text-sm sm:text-base border-b border-green-200 pb-2">
                    <i class="fas fa-bolt mr-2"></i>Quick Access Modules
                </h4>

                <div class="flex flex-col gap-2 text-xs sm:text-sm">
                    <a href="sanchari_register.php" class="bg-white text-gray-800 font-semibold p-3 rounded-xl border border-green-200 hover:bg-green-50 transition-all flex items-center">
                        <i class="fas fa-user-plus w-6 text-green-700 mr-2"></i> Customer Registration
                    </a>
                    <a href="sanchari_payment.php" class="bg-white text-gray-800 font-semibold p-3 rounded-xl border border-green-200 hover:bg-green-50 transition-all flex items-center">
                        <i class="fas fa-coins w-6 text-yellow-700 mr-2"></i> Payment Entry
                    </a>
                    <a href="sanchari_dashboard.php" class="bg-white text-gray-800 font-semibold p-3 rounded-xl border border-green-200 hover:bg-green-50 transition-all flex items-center">
                        <i class="fas fa-chart-line w-6 text-blue-700 mr-2"></i> Dashboard
                    </a>
                    <a href="sanchari_redemption.php" class="bg-white text-gray-800 font-semibold p-3 rounded-xl border border-green-200 hover:bg-green-50 transition-all flex items-center">
                        <i class="fas fa-gem w-6 text-red-700 mr-2"></i> Gold Redemption
                    </a>
                    <a href="sanchari_reports.php" class="bg-white text-gray-800 font-semibold p-3 rounded-xl border border-green-200 hover:bg-green-50 transition-all flex items-center">
                        <i class="fas fa-file-alt w-6 text-purple-700 mr-2"></i> Reports & Audits
                    </a>
                </div>
            </div>

            <div class="form-card p-4 sm:p-6">
                <h4 class="font-bold text-red-900 mb-3 text-sm sm:text-base">Scheme Terms & Conditions</h4>
                <ol class="text-xs text-gray-700 space-y-2 list-decimal pl-4">
                    <li>Deposit continuously for 11 months and receive scheme benefit in the 12th month.</li>
                    <li>Passbook loss requires customer verification.</li>
                    <li>Gold weight is calculated as per the gold rate on the payment date.</li>
                    <li>Missing installments may cancel scheme benefits.</li>
                </ol>
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
