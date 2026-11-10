<?php
require_once 'includes/header.php';
addLog('Viewed sales page');

// Get sales statistics
try {
    // Daily sales
    $daily_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
    
    // Weekly sales
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    $weekly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) BETWEEN '$week_start' AND '$week_end'")->fetch();
    
    // Monthly sales
    $monthly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch();
    
    // Total transactions
    $total_transactions = $pdo->query("SELECT COUNT(*) as total FROM transactions")->fetch();
    
    // Monthly sales data for chart
    $monthly_data = $pdo->query("
        SELECT MONTHNAME(transaction_date) as month, 
               COALESCE(SUM(amount), 0) as total
        FROM transactions 
        WHERE YEAR(transaction_date) = YEAR(CURDATE())
        GROUP BY MONTH(transaction_date)
        ORDER BY MONTH(transaction_date)
    ")->fetchAll();
    
    // Service distribution data
    $service_distribution = $pdo->query("
        SELECT s.name, COUNT(t.id) as count, COALESCE(SUM(t.amount), 0) as total
        FROM transactions t
        JOIN services s ON t.service_id = s.id
        WHERE MONTH(t.transaction_date) = MONTH(CURDATE())
        GROUP BY t.service_id
        ORDER BY count DESC
        LIMIT 5
    ")->fetchAll();
    
    // Initial recent transactions (without filter)
    $recent_transactions = $pdo->query("
        SELECT t.*, b.first_name, b.last_name, s.name as service_name 
        FROM transactions t 
        JOIN barbers b ON t.barber_id = b.id 
        JOIN services s ON t.service_id = s.id 
        ORDER BY t.transaction_date DESC 
        LIMIT 20
    ")->fetchAll();
    
    // Total count for pagination
    $total_count = $pdo->query("SELECT COUNT(*) as total FROM transactions")->fetch();
    
} catch (PDOException $e) {
    $error = "Error loading sales data: " . $e->getMessage();
}

// Prepare data for charts
$monthly_labels = [];
$monthly_totals = [];
$service_labels = [];
$service_counts = [];

foreach ($monthly_data as $data) {
    $monthly_labels[] = $data['month'];
    $monthly_totals[] = $data['total'];
}

foreach ($service_distribution as $data) {
    $service_labels[] = $data['name'];
    $service_counts[] = $data['count'];
}
?>
<div class="mx-3 my-4 fade-in px-4">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Sales Analytics</h2>
    
    <?php if (isset($error)): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Sales Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Today's Sales</p>
                    <h3 class="text-xl font-bold text-gray-800">₱<?php echo number_format($daily_sales['total'] ?? 0, 2); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-200 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-day text-blue-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">This Week</p>
                    <h3 class="text-xl font-bold text-gray-800">₱<?php echo number_format($weekly_sales['total'] ?? 0, 2); ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-200 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-week text-green-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">This Month</p>
                    <h3 class="text-xl font-bold text-gray-800">₱<?php echo number_format($monthly_sales['total'] ?? 0, 2); ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-200 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-purple-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Total Transactions</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo number_format($total_transactions['total'] ?? 0); ?></h3>
                </div>
                <div class="w-12 h-12 bg-orange-200 rounded-xl flex items-center justify-center">
                    <i class="fas fa-receipt text-orange-600 text-lg"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Monthly Sales Trend</h3>
            <div class="h-64">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Service Distribution (This Month)</h3>
            <div class="h-64">
                <canvas id="serviceDistributionChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Detailed Sales Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b bg-gray-50">
            <div class="flex flex-col space-y-4">
                <!-- Header Title -->
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Transaction History</h3>
                        <p class="text-sm text-gray-500 mt-1">View and filter your sales transactions</p>
                    </div>
                    <div class="text-sm text-gray-500 md:hidden">
                        <i class="fas fa-filter mr-1"></i>Use filters below
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Filter Controls -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:w-auto">
                        <!-- Date Filter -->
                        <div class="flex-1 sm:flex-none">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap hidden sm:block">Date:</label>
                                <input type="date" id="filterDate" 
                                       class="w-full sm:w-40 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all duration-200">
                            </div>
                        </div>
                        
                        <!-- Barber Filter -->
                        <div class="flex-1 sm:flex-none">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600 whitespace-nowrap hidden sm:block">Barber:</label>
                                <select id="filterBarber" 
                                        class="w-full sm:w-48 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all duration-200">
                                    <option value="">All Barbers</option>
                                    <?php
                                    try {
                                        $barbers = $pdo->query("SELECT * FROM barbers ORDER BY first_name")->fetchAll();
                                        foreach ($barbers as $barber):
                                    ?>
                                    <option value="<?php echo $barber['id']; ?>">
                                        <?php echo htmlspecialchars($barber['first_name'] . ' ' . $barber['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <?php } catch (PDOException $e) { ?>
                                    <option value="">Error loading barbers</option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button onclick="filterTransactions()" 
                                    class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-filter"></i>
                                <span>Filter</span>
                            </button>
                            <button onclick="resetFilter()" 
                                    class="flex-1 sm:flex-none bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-redo"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Transaction Count -->
                    <div class="text-sm text-gray-600 bg-white px-3 py-2 rounded-lg border border-gray-200">
                        Total: <span id="totalCount" class="font-semibold"><?php echo number_format($total_count['total']); ?></span> transactions
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scrollable Table Container -->
        <div class="overflow-hidden">
            <div class="overflow-x-auto max-h-[500px] scrollable-table">
                <table class="w-full min-w-full">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">ID</th>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Date & Time</th>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Barber</th>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Service</th>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Amount</th>
                            <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTable" class="divide-y divide-gray-100">
                        <?php if (count($recent_transactions) > 0): ?>
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150 even:bg-gray-50/50">
                                <td class="p-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($transaction['transaction_number']); ?></span>
                                </td>
                                <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-medium"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></span>
                                        <span class="text-gray-500 text-xs"><?php echo date('h:i A', strtotime($transaction['transaction_date'])); ?></span>
                                    </div>
                                </td>
                                <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600 text-xs"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></span>
                                    </div>
                                </td>
                                <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        <?php echo htmlspecialchars($transaction['service_name']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm font-semibold text-green-700 whitespace-nowrap">
                                    ₱<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-receipt text-gray-300 text-4xl mb-4"></i>
                                        <p class="text-gray-600 font-medium text-lg">No transactions found</p>
                                        <p class="text-sm text-gray-400 mt-2">Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination & Info Footer -->
        <div class="px-4 py-4 border-t bg-gray-50">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-600">
                    Showing <span id="showingCount" class="font-semibold"><?php echo min(20, count($recent_transactions)); ?></span> 
                    of <span id="totalCountFooter" class="font-semibold"><?php echo number_format($total_count['total']); ?></span> transactions
                </div>
                
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2" 
                            id="prevBtn" disabled>
                        <i class="fas fa-chevron-left"></i>
                        <span>Prev</span>
                    </button>
                    <span class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium" id="pageNum">1</span>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2" 
                            id="nextBtn" <?php echo count($recent_transactions) < 20 ? 'disabled' : ''; ?>>
                        <span>Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar for table */
.scrollable-table {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.scrollable-table::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.scrollable-table::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.scrollable-table::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.scrollable-table::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sticky header for scrollable table */
.scrollable-table thead {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-1 {
        gap: 0.75rem;
    }
    
    .scrollable-table {
        max-height: 400px;
    }
    
    table {
        font-size: 12px;
    }
    
    td, th {
        padding: 12px;
    }
    
    .p-5 {
        padding: 1.25rem;
    }
    
    .text-xl {
        font-size: 1.25rem;
    }
    
    .rounded-xl {
        border-radius: 0.75rem;
    }
}

@media (max-width: 640px) {
    .scrollable-table {
        max-height: 350px;
    }
    
    .p-4 {
        padding: 0.75rem;
    }
    
    .text-lg {
        font-size: 1.125rem;
    }
    
    /* Stack filter inputs vertically on mobile */
    .sm\:flex-row {
        flex-direction: column;
    }
    
    .sm\:w-40,
    .sm\:w-48 {
        width: 100% !important;
    }
    
    /* Adjust chart height on mobile */
    .h-64 {
        height: 200px;
    }
}

/* Hover effects for better UX */
.hover\:shadow-md {
    transition: box-shadow 0.2s ease-in-out;
}

/* Animation for table rows */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#transactionsTable tr {
    animation: fadeIn 0.3s ease-out;
}

/* Loading spinner animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

<script>
// Monthly Sales Chart
document.addEventListener('DOMContentLoaded', function() {
    const monthlyCtx = document.getElementById('monthlySalesChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthly_labels); ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?php echo json_encode($monthly_totals); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(59, 130, 246, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Service Distribution Chart
    const serviceCtx = document.getElementById('serviceDistributionChart');
    if (serviceCtx) {
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($service_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_counts); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(139, 92, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverBorderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' transactions';
                            }
                        }
                    }
                }
            }
        });
    }
});

function filterTransactions() {
    const date = document.getElementById('filterDate').value;
    const barberId = document.getElementById('filterBarber').value;
    
    // Build query parameters
    let params = new URLSearchParams();
    if (date) params.append('date', date);
    if (barberId) params.append('barber_id', barberId);
    
    // Show loading state
    const tableBody = document.getElementById('transactionsTable');
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="p-12 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-[3px] border-blue-600 border-t-transparent mb-4"></div>
                    <p class="text-gray-600 font-medium">Loading filtered results...</p>
                    <p class="text-sm text-gray-400 mt-1">Please wait</p>
                </div>
            </td>
        </tr>
    `;
    
    // AJAX call to filter transactions
    fetch('api/filter_transactions.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTransactionsTable(data.transactions);
                updateTransactionCounts(data.total_count, data.showing_count);
            } else {
                showError(data.message || 'Error filtering transactions');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Network error. Please try again.');
        });
}

function updateTransactionsTable(transactions) {
    const tableBody = document.getElementById('transactionsTable');
    
    if (transactions.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="p-12 text-center text-gray-500">
                    <div class="flex flex-col items-center justify-center">
                        <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-600 font-medium text-lg">No transactions found</p>
                        <p class="text-sm text-gray-400 mt-2">Try different filter criteria</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    transactions.forEach(transaction => {
        const date = new Date(transaction.transaction_date);
        const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        html += `
        <tr class="hover:bg-gray-50 transition-colors duration-150 even:bg-gray-50/50">
            <td class="p-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs">${transaction.transaction_number}</span>
            </td>
            <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                <div class="flex flex-col">
                    <span class="font-medium">${formattedDate}</span>
                    <span class="text-gray-500 text-xs">${formattedTime}</span>
                </div>
            </td>
            <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-xs"></i>
                    </div>
                    <span>${transaction.first_name} ${transaction.last_name}</span>
                </div>
            </td>
            <td class="p-4 text-sm text-gray-700 whitespace-nowrap">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                    ${transaction.service_name}
                </span>
            </td>
            <td class="p-4 text-sm font-semibold text-green-700 whitespace-nowrap">
                ₱${parseFloat(transaction.amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}
            </td>
            <td class="p-4 whitespace-nowrap">
                <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                    ${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}
                </span>
            </td>
        </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function updateTransactionCounts(totalCount, showingCount) {
    document.getElementById('totalCount').textContent = totalCount.toLocaleString();
    document.getElementById('showingCount').textContent = showingCount;
    document.getElementById('totalCountFooter').textContent = totalCount.toLocaleString();
    
    // Update pagination button states
    const nextBtn = document.getElementById('nextBtn');
    nextBtn.disabled = showingCount < 20;
}

function showError(message) {
    const tableBody = document.getElementById('transactionsTable');
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="p-12 text-center">
                <div class="flex flex-col items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                    <p class="text-red-600 font-medium">${message}</p>
                    <p class="text-sm text-gray-400 mt-2">Please try again</p>
                </div>
            </td>
        </tr>
    `;
}

function resetFilter() {
    document.getElementById('filterDate').value = '';
    document.getElementById('filterBarber').value = '';
    location.reload();
}
</script>

<?php require_once 'includes/footer.php'; ?>