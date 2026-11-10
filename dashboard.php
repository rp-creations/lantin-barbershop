<?php
// Start session and include database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

addLog('Viewed dashboard', 'Accessed dashboard page');

// Get statistics
try {
    // Daily sales
    $daily_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
    
    // Monthly sales
    $monthly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch();
    
    // Total active barbers
    $total_barbers = $pdo->query("SELECT COUNT(*) as count FROM barbers WHERE is_active = 1")->fetch();
    
    // Low stock items
    $low_stock_items = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level")->fetch();
    
    // Today's summary
    $today_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(DISTINCT barber_id) as barbers_active,
            COALESCE(SUM(amount), 0) as total_revenue
        FROM transactions 
        WHERE DATE(transaction_date) = CURDATE()
    ")->fetch();
    
    // Recent transactions - CHANGED TO 3 INSTEAD OF 5
    $recent_transactions = $pdo->query("
        SELECT t.*, b.first_name, b.last_name, s.name as service_name 
        FROM transactions t 
        JOIN barbers b ON t.barber_id = b.id 
        JOIN services s ON t.service_id = s.id 
        ORDER BY t.transaction_date DESC 
        LIMIT 3
    ")->fetchAll();
    
    // Low stock items details
    $low_stock = $pdo->query("
        SELECT name, quantity, reorder_level 
        FROM inventory 
        WHERE quantity <= reorder_level 
        ORDER BY quantity ASC 
        LIMIT 4
    ")->fetchAll();
    
    // Weekly sales data for chart
    $weekly_sales = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('D', strtotime("-$i days"));
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = ?");
        $stmt->execute([$date]);
        $day_sales = $stmt->fetch();
        
        $weekly_sales[] = [
            'day' => $day_name,
            'sales' => $day_sales['total']
        ];
    }
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Now include the header
require_once 'includes/header.php';
?>
<div class="fade-in mx-6">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600 mt-1">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'Guest')[0]); ?>!</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <?php echo date('F j, Y'); ?>
                </span>
                <button onclick="location.reload()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Daily Sales -->
        <div class="card p-5 bg-white border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Daily Sales</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">₱<?php echo number_format($daily_sales['total'] ?? 0, 2); ?></p>
                    <p class="text-xs text-green-600 mt-1">Today's revenue</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-white text-lg"></i>
                </div>
            </div>
        </div>
        
        <!-- Monthly Sales -->
        <div class="card p-5 bg-white border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Monthly Sales</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">₱<?php echo number_format($monthly_sales['total'] ?? 0, 2); ?></p>
                    <p class="text-xs text-green-600 mt-1">This month</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-bar text-white text-lg"></i>
                </div>
            </div>
        </div>
        
        <!-- Active Barbers -->
        <div class="card p-5 bg-white border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Active Barbers</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?php echo $total_barbers['count'] ?? 0; ?></p>
                    <p class="text-xs text-gray-600 mt-1">Currently working</p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-tie text-white text-lg"></i>
                </div>
            </div>
        </div>
        
        <!-- Low Stock -->
        <div class="card p-5 bg-white border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Low Stock Items</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?php echo $low_stock_items['count'] ?? 0; ?></p>
                    <p class="text-xs text-red-600 mt-1">Need restocking</p>
                </div>
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white text-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Sales Chart -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Weekly Sales Overview</h3>
            </div>
            <div class="chart-container h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800">Recent Transactions</h3>
                <a href="transactions.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All →
                </a>
            </div>
            
            <div class="space-y-4">
                <?php if (count($recent_transactions) > 0): ?>
                    <?php foreach ($recent_transactions as $index => $transaction): ?>
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-receipt text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($transaction['service_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-800">₱<?php echo number_format($transaction['amount'], 2); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-receipt text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No transactions yet</p>
                    <a href="transactions.php" class="inline-block mt-3 text-blue-600 hover:text-blue-800">
                        Create your first transaction →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="card p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="transactions.php" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Add Transaction</p>
                        <p class="text-xs text-gray-500">Record new sale</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="inventory.php" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-boxes text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Manage Inventory</p>
                        <p class="text-xs text-gray-500">Check stock levels</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="barbers.php" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-user-tie text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Barber Management</p>
                        <p class="text-xs text-gray-500">View barber stats</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>
        
        <!-- Low Stock Items -->
        <div class="card p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Low Stock Alert</h3>
            <?php if (count($low_stock) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($low_stock as $item): 
                    $percentage = min(100, ($item['quantity'] / max(1, $item['reorder_level'])) * 100);
                ?>
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="text-red-600 font-bold"><?php echo $item['quantity']; ?> left</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500">Reorder at: <?php echo $item['reorder_level']; ?> units</p>
                </div>
                <?php endforeach; ?>
                <a href="inventory.php" class="block mt-4 text-center text-blue-600 hover:text-blue-800 text-sm">
                    Manage All Inventory →
                </a>
            </div>
            <?php else: ?>
            <div class="text-center py-6">
                <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
                <p class="text-gray-600">All items are well-stocked!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Today's Summary -->
        <div class="card p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Today's Summary</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-shopping-cart text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Transactions</p>
                            <p class="text-xs text-gray-500">Today's sales</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold"><?php echo $today_stats['total_transactions'] ?? 0; ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-user-tie text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Active Barbers</p>
                            <p class="text-xs text-gray-500">Working today</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold"><?php echo $today_stats['barbers_active'] ?? 0; ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-money-bill-wave text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Today's Revenue</p>
                            <p class="text-xs text-gray-500">Total earnings</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-green-600">₱<?php echo number_format($today_stats['total_revenue'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const weeklyData = <?php echo json_encode($weekly_sales); ?>;
        const labels = weeklyData.map(item => item.day);
        const data = weeklyData.map(item => item.sales);
        
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: data,
                    borderColor: '#8a5238',
                    backgroundColor: 'rgba(138, 82, 56, 0.10)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#8a5238',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>