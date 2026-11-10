<?php
// Handle form submission FIRST, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    // Start session for form processing
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once 'config/database.php';
    
    $barber_id = $_POST['barber_id'];
    $service_id = $_POST['service_id'];
    $amount = $_POST['amount'];
    
    // Validate inputs
    if (empty($barber_id) || empty($service_id) || empty($amount)) {
        $_SESSION['error'] = "All fields are required!";
    } elseif ($amount <= 0) {
        $_SESSION['error'] = "Amount must be greater than 0!";
    } else {
        $transaction_number = 'TRX' . strtoupper(uniqid());
        
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_number, barber_id, service_id, amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$transaction_number, $barber_id, $service_id, $amount]);
            
            addLog('Added transaction', "Transaction #$transaction_number for ₱" . number_format($amount, 2));
            
            $_SESSION['success'] = "Transaction added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding transaction: " . (strpos($e->getMessage(), 'foreign key') !== false ? 'Invalid barber or service selected' : $e->getMessage());
        }
    }
    
    // Redirect IMMEDIATELY
    header('Location: transactions.php');
    exit();
}

require_once 'includes/header.php';

$transaction_number = 'TRX' . strtoupper(uniqid());

$success = '';
$error = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<div class="fade-in">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Add Transaction</h2>
        
        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <!-- Form and Recent Transactions in Responsive Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
            <!-- Form Section -->
            <div class="w-full">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 h-full">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 pb-4 border-b border-gray-100">Add New Transaction</h3>
                    <form method="POST" class="w-full">
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2 text-sm font-medium">Transaction Number</label>
                            <input type="text" value="<?php echo $transaction_number; ?>" 
                                   class="w-full p-3.5 border border-gray-300 rounded-lg bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" readonly>
                            <p class="text-xs text-gray-500 mt-2">Automatically generated</p>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2 text-sm font-medium">Barber *</label>
                            <select name="barber_id" class="w-full p-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200" required>
                                <option value="">Select barber...</option>
                                <?php
                                try {
                                    $barbers = $pdo->query("SELECT * FROM barbers WHERE is_active = 1 ORDER BY first_name")->fetchAll();
                                    if (count($barbers) > 0) {
                                        foreach ($barbers as $barber):
                                ?>
                                <option value="<?php echo $barber['id']; ?>">
                                    <?php echo htmlspecialchars($barber['barber_number'] . ' - ' . $barber['first_name'] . ' ' . $barber['last_name']); ?>
                                </option>
                                <?php 
                                        endforeach;
                                    } else {
                                        echo '<option value="">No barbers available</option>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<option value="">Error loading barbers</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2 text-sm font-medium">Service *</label>
                            <select name="service_id" id="serviceSelect" class="w-full p-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200" required>
                                <option value="">Select service...</option>
                                <?php
                                try {
                                    $services = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name")->fetchAll();
                                    if (count($services) > 0) {
                                        foreach ($services as $service):
                                ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                    <?php echo htmlspecialchars($service['name'] . ' - ₱' . number_format($service['price'], 2)); ?>
                                </option>
                                <?php 
                                        endforeach;
                                    } else {
                                        echo '<option value="">No services available</option>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<option value="">Error loading services</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-8">
                            <label class="block text-gray-700 mb-2 text-sm font-medium">Amount (₱) *</label>
                            <input type="number" name="amount" id="amountInput" 
                                   class="w-full p-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200" 
                                   step="0.01" min="0.01" required 
                                   placeholder="0.00">
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-100">
                            <button type="submit" name="add_transaction" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i>
                                <span>Add Transaction</span>
                            </button>
                            <button type="reset" 
                                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3.5 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-redo"></i>
                                <span>Reset Form</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Transactions Section -->
            <div class="w-full">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 h-full">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800">Recent Transactions</h3>
                        <span class="text-xs text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg font-medium">
                            <i class="fas fa-history mr-1.5"></i>Latest 20
                        </span>
                    </div>
                    
                    <div class="overflow-y-auto pr-2" style="max-height: 380px;">
                        <?php
                        try {
                            $recent_transactions = $pdo->query("
                                SELECT t.*, b.first_name, b.last_name, s.name as service_name 
                                FROM transactions t 
                                JOIN barbers b ON t.barber_id = b.id 
                                JOIN services s ON t.service_id = s.id 
                                ORDER BY t.transaction_date DESC 
                                LIMIT 20
                            ")->fetchAll();
                            
                            if (count($recent_transactions) > 0):
                        ?>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-gray-50 z-10 border-b border-gray-200">
                                    <tr>
                                        <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Transaction</th>
                                        <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Date</th>
                                        <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Barber</th>
                                        <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Service</th>
                                        <th class="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($recent_transactions as $tx): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="p-4 text-xs whitespace-nowrap">
                                            <span class="font-mono bg-gray-100 px-2.5 py-1 rounded text-xs text-gray-700 font-medium"><?php echo htmlspecialchars($tx['transaction_number']); ?></span>
                                        </td>
                                        <td class="p-4 text-xs whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-800"><?php echo date('M d', strtotime($tx['transaction_date'])); ?></span>
                                                <span class="text-gray-500 text-xs"><?php echo date('h:i A', strtotime($tx['transaction_date'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-xs whitespace-nowrap">
                                            <span class="font-medium text-gray-700"><?php echo htmlspecialchars(substr($tx['first_name'], 0, 1) . '. ' . $tx['last_name']); ?></span>
                                        </td>
                                        <td class="p-4 text-xs whitespace-nowrap">
                                            <span class="truncate max-w-[80px] md:max-w-[120px] inline-block align-middle text-gray-600" title="<?php echo htmlspecialchars($tx['service_name']); ?>">
                                                <?php echo htmlspecialchars($tx['service_name']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-xs whitespace-nowrap font-bold text-green-600">
                                            ₱<?php echo number_format($tx['amount'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-12 px-4 border-2 border-dashed border-gray-200 rounded-xl">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-600 font-medium mb-2">No transactions yet</p>
                            <p class="text-sm text-gray-400">Start adding transactions to see them here</p>
                        </div>
                        <?php endif; ?>
                        <?php } catch (PDOException $e) { ?>
                        <div class="text-center py-12 px-4 border-2 border-dashed border-red-200 rounded-xl bg-red-50">
                            <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                            </div>
                            <p class="text-red-600 font-medium mb-2">Error loading transactions</p>
                            <p class="text-sm text-red-400">Please try refreshing the page</p>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <!-- Stats Summary -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-4">Today's Summary</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <?php
                            try {
                                $today_stats = $pdo->query("
                                    SELECT 
                                        COUNT(*) as count,
                                        COALESCE(SUM(amount), 0) as total
                                    FROM transactions 
                                    WHERE DATE(transaction_date) = CURDATE()
                                ")->fetch();
                                
                                $month_stats = $pdo->query("
                                    SELECT 
                                        COUNT(*) as count,
                                        COALESCE(SUM(amount), 0) as total
                                    FROM transactions 
                                    WHERE MONTH(transaction_date) = MONTH(CURDATE()) 
                                    AND YEAR(transaction_date) = YEAR(CURDATE())
                                ")->fetch();
                            ?>
                            <div class="bg-white rounded-xl p-4 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-blue-800 font-medium mb-1">Today</p>
                                        <p class="text-xl font-bold text-blue-900"><?php echo $today_stats['count']; ?></p>
                                        <p class="text-xs text-blue-700 mt-1">transactions</p>
                                    </div>
                                    <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-calendar-day text-blue-700"></i>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-blue-900 mt-2">₱<?php echo number_format($today_stats['total'], 2); ?></p>
                            </div>
                            <div class="bg-white rounded-xl p-4 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-green-800 font-medium mb-1">This Month</p>
                                        <p class="text-xl font-bold text-green-900"><?php echo $month_stats['count']; ?></p>
                                        <p class="text-xs text-green-700 mt-1">transactions</p>
                                    </div>
                                    <div class="w-10 h-10 bg-green-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-calendar-alt text-green-700"></i>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-green-900 mt-2">₱<?php echo number_format($month_stats['total'], 2); ?></p>
                            </div>
                            <?php } catch (PDOException $e) { ?>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 col-span-2">
                                <div class="flex items-center justify-center gap-3">
                                    <i class="fas fa-exclamation-circle text-gray-400"></i>
                                    <p class="text-sm text-gray-600">Stats unavailable</p>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Ensure equal width containers */
.grid-cols-1 > div {
    width: 100%;
}

/* Animation for table rows */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody tr {
    animation: fadeInUp 0.3s ease-out forwards;
    opacity: 0;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .px-8 {
        padding-left: 2rem;
        padding-right: 2rem;
    }
    
    .gap-8 {
        gap: 1.5rem;
    }
    
    .overflow-y-auto {
        max-height: 350px;
    }
}

@media (max-width: 768px) {
    .px-8 {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .py-6 {
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
    }
    
    .p-6 {
        padding: 1.25rem;
    }
    
    .p-4 {
        padding: 0.875rem;
    }
    
    .overflow-y-auto {
        max-height: 320px;
    }
    
    table {
        font-size: 11px;
    }
    
    .text-2xl {
        font-size: 1.5rem;
    }
}

@media (max-width: 640px) {
    .px-8 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .gap-8 {
        gap: 1rem;
    }
    
    .grid-cols-1 {
        gap: 1.5rem;
    }
    
    .p-6 {
        padding: 1rem;
    }
    
    .p-4 {
        padding: 0.75rem;
    }
    
    .text-xl {
        font-size: 1.125rem;
    }
}

/* Ensure form elements are consistent */
input, select {
    font-family: inherit;
}

/* Button hover effects */
button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

button {
    transition: all 0.2s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('serviceSelect');
    const amountInput = document.getElementById('amountInput');
    
    // Auto-fill amount when service is selected
    serviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        if (price) {
            amountInput.value = parseFloat(price).toFixed(2);
            amountInput.focus();
        }
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const amount = amountInput.value;
        
        if (parseFloat(amount) <= 0) {
            e.preventDefault();
            showAlert('error', 'Amount must be greater than 0.');
            return false;
        }
        
        return true;
    });
    
    // Auto-focus on first input for better UX
    const firstSelect = document.querySelector('select[name="barber_id"]');
    if (firstSelect) {
        firstSelect.focus();
    }
    
    // Add staggered animation to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Reset form function
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        amountInput.value = '';
        serviceSelect.selectedIndex = 0;
        document.querySelector('select[name="barber_id"]').selectedIndex = 0;
    });
    
    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `mb-4 p-4 rounded-lg flex items-center alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'} mr-2"></i>
            ${message}
        `;
        
        const alertsContainer = document.querySelector('.px-8 .mb-6');
        if (alertsContainer) {
            alertsContainer.parentNode.insertBefore(alertDiv, alertsContainer);
            setTimeout(() => alertDiv.remove(), 3000);
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>