<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

set_security_headers();

// Check if user is logged in for protected pages
$protected_pages = ['dashboard.php', 'transactions.php', 'sales.php', 'inventory.php', 'barbers.php', 'manage.php', 'logs.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    addLog('Logout', "User logged out");
    session_destroy();
    header('Location: login.php');
    exit();
}

// Redirect to login if not authenticated for protected pages
if (in_array($current_page, $protected_pages)) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Get user info if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();
    } catch (PDOException $e) {
        $current_user = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LANTIN - Barbershop Management System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .sidebar {
            transition: all 0.3s ease;
            width: 16rem;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
            width: 0;
            opacity: 0;
            visibility: hidden;
        }
        .main-content {
            transition: all 0.3s ease;
        }
        .sidebar-toggle {
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #8a5238;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease;
        }
        
        /* Fixed header styling */
        .fixed-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 30;
            background: white;
        }
        
        /* Main content spacing for fixed header and sidebar */
        .main-content-wrapper {
            margin-top: 64px; /* Header height */
            transition: margin-left 0.3s ease;
        }
        
        /* Desktop: account for minimal sidebar */
        @media (min-width: 768px) {
            .fixed-header {
                left: 64px; /* Minimal sidebar width */
            }
            
            .main-content-wrapper {
                margin-left: 64px; /* Minimal sidebar width */
            }
        }
        
        /* Mobile: full width */
        @media (max-width: 767px) {
            .fixed-header {
                left: 0;
            }
            
            .main-content-wrapper {
                margin-left: 0;
            }
        }
        
        /* Ensure body has proper spacing */
        body {
            min-height: 100vh;
        }
    </style>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body class="bg-gray-50">
    <?php if (!in_array($current_page, ['login.php', 'signup.php', 'test_db.php'])): ?>
    
    <!-- Minimal Sidebar is included from sidebar.php -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Fixed Top Navigation -->
    <nav class="fixed-header shadow-sm px-4 sm:px-6 py-[10px] border-b border-gray-200">
        <div class="flex justify-between n-0 items-center">
            <div class="flex items-center">
                <button id="sidebarToggle" class="sidebar-toggle text-gray-600 hover:text-gray-900 mr-3 sm:mr-4 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <!-- Hamburger button - shows full sidebar -->
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">LANTIN.</h1>
                    <span class="text-sm text-gray-600 hidden sm:inline"><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
            <div class="flex items-center">
                <div class="mr-3 sm:mr-4 text-right hidden sm:block">
                    <p class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?></p>
                    <p class="text-xs sm:text-sm text-gray-600"><?php echo htmlspecialchars($_SESSION['user_position'] ?? ''); ?></p>
                </div>  
                <button onclick="showLogoutModal()" class="bg-red-500 hover:bg-red-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base transition-colors">
                    <i class="fas fa-sign-out-alt mr-1 sm:mr-2"></i>
                    <span class="hidden sm:inline">Log Out</span>
                    <span class="sm:hidden">Exit</span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <main class="p-4 sm:p-6">
    <?php endif; ?>