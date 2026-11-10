<?php
// DO NOT start session here - it's already started in header.php
?>

<!-- Minimal Sidebar (Icons Only) -->
<div class="minimal-sidebar bg-gray-900 text-white min-h-screen w-16 fixed left-0 top-0 bottom-0 z-40 shadow-lg transition-all duration-300 hidden lg:block">
    <div class="flex flex-col items-center py-6 space-y-8">
        <!-- Logo - Clickable to open full sidebar -->
        <button id="logoOpenSidebar" class="w-10 h-10 bg-amber-700 rounded-lg flex items-center justify-center hover:opacity-90 transition-opacity cursor-pointer hover:scale-105 transition-transform duration-200" title="Open Menu" aria-label="Open navigation menu">
            <i class="fas fa-cut"></i>
        </button>
        
        <!-- Navigation Icons -->
        <nav class="flex-1 w-full">
            <ul class="space-y-6">
                <li class="flex justify-center">
                    <a href="dashboard.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>" title="Dashboard">
                        <i class="fas fa-tachometer-alt text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="transactions.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'bg-gray-700' : ''; ?>" title="Add Transaction">
                        <i class="fas fa-exchange-alt text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="sales.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'bg-gray-700' : ''; ?>" title="Sales Analytics">
                        <i class="fas fa-chart-line text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="inventory.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-gray-700' : ''; ?>" title="Inventory">
                        <i class="fas fa-boxes text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="barbers.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'barbers.php' ? 'bg-gray-700' : ''; ?>" title="Barbers">
                        <i class="fas fa-user-tie text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="manage.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'bg-gray-700' : ''; ?>" title="Manage Services">
                        <i class="fas fa-cog text-lg"></i>
                    </a>
                </li>
                <li class="flex justify-center">
                    <a href="logs.php" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'bg-gray-700' : ''; ?>" title="System Logs">
                        <i class="fas fa-clipboard-list text-lg"></i>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- User Info (Icon Only) -->
        <div class="mt-auto">
            <div class="w-8 h-8 rounded-full bg-amber-700 flex items-center justify-center text-xs font-bold" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>">
                <?php 
                $name = $_SESSION['user_name'] ?? 'G';
                echo strtoupper(substr($name, 0, 1));
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Full Sidebar (Hidden by default, appears on hamburger click) -->
<div class="full-sidebar bg-gray-900 text-white min-h-screen w-64 fixed left-0 top-0 bottom-0 z-50 shadow-xl transition-all duration-300 transform -translate-x-full">
    <div class="h-full flex flex-col">
        <!-- Fixed Header -->
        <div class="p-4 border-b border-gray-700 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-amber-700 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">LANTIN</h2>
                        <p class="text-gray-400 text-xs">Barbershop Management</p>
                    </div>
                </div>
                <button id="closeFullSidebar" class="text-gray-400 hover:text-white p-2 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4">
                <nav>
                    <ul class="space-y-1">
                        <li>
                            <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-tachometer-alt mr-3"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="transactions.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-exchange-alt mr-3"></i>
                                <span>Add Transaction</span>
                            </a>
                        </li>
                        <li>
                            <a href="sales.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-chart-line mr-3"></i>
                                <span>Sales Analytics</span>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-boxes mr-3"></i>
                                <span>Inventory</span>
                            </a>
                        </li>
                        <li>
                            <a href="barbers.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'barbers.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-user-tie mr-3"></i>
                                <span>Barbers</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-cog mr-3"></i>
                                <span>Manage Services</span>
                            </a>
                        </li>
                        <li>
                            <a href="logs.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'bg-gray-700' : ''; ?> transition-colors">
                                <i class="fas fa-clipboard-list mr-3"></i>
                                <span>System Logs</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="mt-8 pt-6 border-t border-gray-700">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold text-gray-400 mb-2">Quick Stats</h3>
                        <?php
                        global $pdo;
                        try {
                            $daily_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
                            $monthly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch();
                            $today_count = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
                        } catch (PDOException $e) {
                            $daily_sales = ['total' => 0];
                            $monthly_sales = ['total' => 0];
                            $today_count = ['total' => 0];
                        }
                        ?>
                        <div class="space-y-2">
                            <div>
                                <p class="text-xs text-gray-400">Daily Sales</p>
                                <p class="text-sm font-bold text-green-400">₱<?php echo number_format($daily_sales['total'], 2); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Monthly Sales</p>
                                <p class="text-sm font-bold text-purple-400">₱<?php echo number_format($monthly_sales['total'], 2); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Today's Transactions</p>
                                <p class="text-sm font-bold text-blue-400"><?php echo $today_count['total']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add some extra content to make it scrollable -->
                <div class="mt-8 pt-6 border-t border-gray-700">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold text-gray-400 mb-2">Recent Activity</h3>
                        <?php
                        try {
                            $recent_activity = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();
                        } catch (PDOException $e) {
                            $recent_activity = [];
                        }
                        ?>
                        <div class="space-y-3">
                            <?php if (empty($recent_activity)): ?>
                                <p class="text-xs text-gray-500 text-center py-2">No recent activity</p>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $log): ?>
                                    <div class="text-xs">
                                        <p class="text-gray-300 truncate"><?php echo htmlspecialchars($log['action']); ?></p>
                                        <p class="text-gray-500"><?php echo date('h:i A', strtotime($log['created_at'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fixed Footer -->
        <div class="p-4 border-t border-gray-700 flex-shrink-0">
            <p class="text-xs text-gray-500 text-center">
                <span class="block text-gray-400">Logged in as:</span>
                <span class="font-medium"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?></span><br>
                <span class="text-gray-400"><?php echo htmlspecialchars($_SESSION['user_position'] ?? ''); ?></span>
            </p>
        </div>
    </div>
</div>

<!-- Overlay for full sidebar -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

<!-- Main content spacer for minimal sidebar -->
<div class="minimal-sidebar-spacer lg:ml-16"></div>

<style>
/* Full sidebar overlay */
.full-sidebar.active {
    transform: translateX(0);
}

/* Scrollbar styling for full sidebar */
.full-sidebar .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.full-sidebar .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.full-sidebar .overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.full-sidebar .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.full-sidebar .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

/* Instead of hiding body overflow, just prevent overscroll behavior */
body {
    overflow-y: scroll; /* Always show scrollbar to prevent layout shift */
    width: 100%;
}

/* Tablet (768px-1024px): Show minimal sidebar */
@media (min-width: 768px) and (max-width: 1023px) {
    .minimal-sidebar {
        display: block !important;
    }
    
    .minimal-sidebar-spacer {
        margin-left: 64px !important;
    }
    
    .fixed-header {
        left: 64px !important;
    }
    
    .main-content-wrapper {
        margin-left: 64px !important;
    }
}

/* Mobile: show full sidebar instead of minimal */
@media (max-width: 767px) {
    .minimal-sidebar {
        display: none !important;
    }
    
    .minimal-sidebar-spacer {
        margin-left: 0 !important;
    }
    
    .full-sidebar {
        width: 280px;
    }
    
    /* On mobile only, we can disable body scroll when sidebar is open */
    body.sidebar-open-mobile {
        overflow: hidden;
        position: fixed;
        width: 100%;
        height: 100%;
    }
}

/* Desktop (1024px+): Show minimal sidebar */
@media (min-width: 1024px) {
    .minimal-sidebar {
        display: block !important;
    }
    
    .minimal-sidebar-spacer {
        margin-left: 64px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const logoOpenSidebar = document.getElementById('logoOpenSidebar');
    const fullSidebar = document.querySelector('.full-sidebar');
    const closeFullSidebar = document.getElementById('closeFullSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const minimalSidebar = document.querySelector('.minimal-sidebar');
    const body = document.body;
    
    // Function to open full sidebar
    function openSidebar() {
        fullSidebar.classList.add('active');
        sidebarOverlay.classList.remove('hidden');
        sidebarOverlay.style.zIndex = '40';
        
        // Only prevent scrolling on mobile
        if (window.innerWidth < 768) {
            body.classList.add('sidebar-open-mobile');
        }
    }
    
    // Function to close full sidebar
    function closeSidebar() {
        fullSidebar.classList.remove('active');
        sidebarOverlay.classList.add('hidden');
        
        // Remove mobile scroll restriction
        body.classList.remove('sidebar-open-mobile');
    }
    
    // Open full sidebar when hamburger is clicked (from header)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            openSidebar();
        });
    }
    
    // Open full sidebar when logo is clicked (from minimal sidebar)
    if (logoOpenSidebar) {
        logoOpenSidebar.addEventListener('click', function(e) {
            e.preventDefault();
            openSidebar();
        });
    }
    
    // Close full sidebar
    if (closeFullSidebar) {
        closeFullSidebar.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar on overlay click
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking a link
    document.querySelectorAll('.full-sidebar a').forEach(link => {
        link.addEventListener('click', closeSidebar);
    });
    
    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && fullSidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
    
    // Handle initial display based on screen size
    function checkScreenSize() {
        if (window.innerWidth < 768) {
            // Mobile: hide minimal sidebar
            if (minimalSidebar) minimalSidebar.style.display = 'none';
        } else if (window.innerWidth >= 768 && window.innerWidth < 1024) {
            // Tablet: show minimal sidebar
            if (minimalSidebar) minimalSidebar.style.display = 'block';
        } else {
            // Desktop: show minimal sidebar
            if (minimalSidebar) minimalSidebar.style.display = 'block';
        }
    }
    
    // Initial check
    checkScreenSize();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        checkScreenSize();
        
        // Ensure mobile scroll restriction is removed on larger screens
        if (window.innerWidth >= 768) {
            body.classList.remove('sidebar-open-mobile');
        }
    });
});
</script>