<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission and redirects BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    // Handle Add Barber
    if (isset($_POST['add_barber'])) {
        $barber_number = trim($_POST['barber_number'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $hired_date = $_POST['hired_date'] ?? '';
        $section = $_POST['section'] ?? 'A';
        $contact_number = trim($_POST['contact_number'] ?? '');
        $wage_per_day = $_POST['wage_per_day'] ?? 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($barber_number)) {
            $errors[] = 'Barber number is required';
        }
        if (empty($first_name)) {
            $errors[] = 'First name is required';
        }
        if (empty($last_name)) {
            $errors[] = 'Last name is required';
        }
        if (empty($hired_date)) {
            $errors[] = 'Hired date is required';
        }
        if (empty($wage_per_day) || $wage_per_day < 0) {
            $errors[] = 'Valid daily wage is required';
        }
        
        if (empty($errors)) {
            try {
                // Check if barber number already exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM barbers WHERE barber_number = ?");
                $checkStmt->execute([$barber_number]);
                if ($checkStmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Barber number already exists!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO barbers (barber_number, first_name, last_name, hired_date, section, contact_number, wage_per_day) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$barber_number, $first_name, $last_name, $hired_date, $section, $contact_number, $wage_per_day]);
                    
                    addLog('Added barber', "Barber: $first_name $last_name (ID: $barber_number)");
                    $_SESSION['success'] = "Barber added successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding barber: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: barbers.php');
        exit();
    }
    
    // Handle Update Barber
    if (isset($_POST['update_barber'])) {
        $barber_id = $_POST['barber_id'] ?? 0;
        $barber_number = trim($_POST['barber_number'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $hired_date = $_POST['hired_date'] ?? '';
        $section = $_POST['section'] ?? 'A';
        $contact_number = trim($_POST['contact_number'] ?? '');
        $wage_per_day = $_POST['wage_per_day'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($barber_number)) {
            $errors[] = 'Barber number is required';
        }
        if (empty($first_name)) {
            $errors[] = 'First name is required';
        }
        if (empty($last_name)) {
            $errors[] = 'Last name is required';
        }
        if (empty($hired_date)) {
            $errors[] = 'Hired date is required';
        }
        if (empty($wage_per_day) || $wage_per_day < 0) {
            $errors[] = 'Valid daily wage is required';
        }
        
        if (empty($errors)) {
            try {
                // Check if barber number already exists (excluding current barber)
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM barbers WHERE barber_number = ? AND id != ?");
                $checkStmt->execute([$barber_number, $barber_id]);
                if ($checkStmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Barber number already exists!";
                } else {
                    $stmt = $pdo->prepare("UPDATE barbers SET barber_number = ?, first_name = ?, last_name = ?, hired_date = ?, section = ?, contact_number = ?, wage_per_day = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$barber_number, $first_name, $last_name, $hired_date, $section, $contact_number, $wage_per_day, $is_active, $barber_id]);
                    
                    addLog('Updated barber', "Barber ID: $barber_id, Barber: $first_name $last_name");
                    $_SESSION['success'] = "Barber updated successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating barber: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: barbers.php');
        exit();
    }
}

// Handle barber deactivation (soft delete)
if (isset($_GET['delete_barber'])) {
    require_once 'config/database.php';
    
    $barber_id = $_GET['delete_barber'];
    
    try {
        // Soft delete - mark as inactive
        $stmt = $pdo->prepare("UPDATE barbers SET is_active = 0 WHERE id = ?");
        $stmt->execute([$barber_id]);
        addLog('Deactivated barber', "Barber ID: $barber_id");
        $_SESSION['success'] = "Barber deactivated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deactivating barber: " . $e->getMessage();
    }
    
    header('Location: barbers.php');
    exit();
}

// Handle permanent barber deletion
if (isset($_GET['permanent_delete'])) {
    require_once 'config/database.php';
    
    $barber_id = $_GET['permanent_delete'];
    
    try {
        // Check if barber has transactions
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE barber_id = ?");
        $checkStmt->execute([$barber_id]);
        $hasTransactions = $checkStmt->fetchColumn() > 0;
        
        if ($hasTransactions) {
            $_SESSION['error'] = "Cannot delete barber with existing transactions! Deactivate instead.";
        } else {
            // Hard delete
            $stmt = $pdo->prepare("DELETE FROM barbers WHERE id = ?");
            $stmt->execute([$barber_id]);
            addLog('Permanently deleted barber', "Barber ID: $barber_id");
            $_SESSION['success'] = "Barber permanently deleted!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting barber: " . $e->getMessage();
    }
    
    header('Location: barbers.php');
    exit();
}

// Handle barber activation
if (isset($_GET['activate_barber'])) {
    require_once 'config/database.php';
    
    $barber_id = $_GET['activate_barber'];
    
    try {
        $stmt = $pdo->prepare("UPDATE barbers SET is_active = 1 WHERE id = ?");
        $stmt->execute([$barber_id]);
        
        addLog('Activated barber', "Barber ID: $barber_id");
        $_SESSION['success'] = "Barber activated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error activating barber: " . $e->getMessage();
    }
    
    header('Location: barbers.php');
    exit();
}

// Now include the header
require_once 'includes/header.php';

// Check for session messages
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

// Get all barbers
try {
    require_once 'config/database.php';
    $barbers = $pdo->query("SELECT * FROM barbers ORDER BY is_active DESC, first_name")->fetchAll();
} catch (PDOException $e) {
    $barbers = [];
    $error = "Error loading barbers: " . $e->getMessage();
}

// Get barber details for editing
$edit_barber = null;
if (isset($_GET['edit_barber'])) {
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT * FROM barbers WHERE id = ?");
        $stmt->execute([$_GET['edit_barber']]);
        $edit_barber = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error loading barber details: " . $e->getMessage();
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <h2 class="text-2xl font-bold mb-4 md:mb-0">Barbers Management</h2>
        <button onclick="openAddModal()" class="btn-primary text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Add Barber
        </button>
    </div>
    
    <?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (count($barbers) > 0): ?>
            <?php foreach ($barbers as $barber): 
                $is_active = $barber['is_active'] == 1;
                $active_class = $is_active ? 'border-green-200' : 'border-red-200';
                $status_text = $is_active ? 'Active' : 'Inactive';
                $status_color = $is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            ?>
            <div class="card p-6 border-2 <?php echo $active_class; ?>">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gray-800 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        <?php echo strtoupper(substr($barber['first_name'], 0, 1) . substr($barber['last_name'], 0, 1)); ?>
                    </div>
                    <div class="ml-4">
                        <div class="flex items-center">
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($barber['first_name'] . ' ' . $barber['last_name']); ?></h3>
                            <span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        <p class="text-gray-600">ID: <?php echo htmlspecialchars($barber['barber_number']); ?></p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Hired Date</p>
                            <p class="font-semibold"><?php echo date('d/m/Y', strtotime($barber['hired_date'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Section</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($barber['section']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($barber['contact_number'])): ?>
                    <div>
                        <p class="text-sm text-gray-500">Contact Number</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($barber['contact_number']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <p class="text-sm text-gray-500">Wage/Day</p>
                        <p class="font-semibold text-green-600">₱<?php echo number_format($barber['wage_per_day'], 2); ?></p>
                    </div>
                    
                    <?php 
                    // Get barber's today transactions
                    try {
                        $today = date('Y-m-d');
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM transactions WHERE barber_id = ? AND DATE(transaction_date) = ?");
                        $stmt->execute([$barber['id'], $today]);
                        $today_stats = $stmt->fetch();
                    } catch (PDOException $e) {
                        $today_stats = ['count' => 0, 'total' => 0];
                    }
                    ?>
                    <div class="pt-3 border-t">
                        <p class="text-sm text-gray-500">Today's Performance</p>
                        <div class="flex justify-between mt-1">
                            <span class="text-sm">Services: <span class="font-semibold"><?php echo $today_stats['count']; ?></span></span>
                            <span class="text-sm">Revenue: <span class="font-semibold text-green-600">₱<?php echo number_format($today_stats['total'], 2); ?></span></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t flex justify-between">
                    <a href="?edit_barber=<?php echo $barber['id']; ?>" 
                    class="text-blue-600 hover:text-blue-800 font-medium px-2 py-1 hover:bg-blue-50 rounded">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <div class="flex space-x-2">
                        <?php if ($is_active): ?>
                        <button onclick="deactivateBarber(<?php echo $barber['id']; ?>)" 
                                class="text-yellow-600 hover:text-yellow-800 font-medium px-2 py-1 hover:bg-yellow-50 rounded">
                            <i class="fas fa-user-slash mr-1"></i>Deactivate
                        </button>
                        <?php else: ?>
                        <button onclick="activateBarber(<?php echo $barber['id']; ?>)" 
                                class="text-green-600 hover:text-green-800 font-medium px-2 py-1 hover:bg-green-50 rounded">
                            <i class="fas fa-user-check mr-1"></i>Activate
                        </button>
                        <?php endif; ?>
                       <button onclick="deleteBarber(<?php echo $barber['id']; ?>, <?php echo $is_active ? 'true' : 'false'; ?>)" 
        class="text-red-600 hover:text-red-800 font-medium px-2 py-1 hover:bg-red-50 rounded">
    <i class="fas fa-trash-alt mr-1"></i>Delete
</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-8">
                <i class="fas fa-user-tie text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No barbers found</p>
                <button onclick="openAddModal()" class="mt-4 btn-primary text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Add Your First Barber
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Barber Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Add New Barber</h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Barber Number *</label>
                    <input type="text" name="barber_number" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., 01, 02, etc.">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="first_name" required 
                               class="w-full p-3 border rounded-lg"
                               placeholder="John">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="last_name" required 
                               class="w-full p-3 border rounded-lg"
                               placeholder="Doe">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Hired Date *</label>
                        <input type="date" name="hired_date" required 
                               class="w-full p-3 border rounded-lg"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Section</label>
                        <select name="section" class="w-full p-3 border rounded-lg">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Contact Number</label>
                    <input type="tel" name="contact_number" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0912 345 6789">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Wage/Day (₱) *</label>
                    <input type="number" name="wage_per_day" step="0.01" min="0" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="500.00" value="500.00">
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeAddModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="add_barber" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Add Barber
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Barber Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Edit Barber</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if ($edit_barber): ?>
        <form method="POST">
            <input type="hidden" name="barber_id" value="<?php echo $edit_barber['id']; ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Barber Number *</label>
                    <input type="text" name="barber_number" value="<?php echo htmlspecialchars($edit_barber['barber_number']); ?>" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., 01, 02, etc.">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_barber['first_name']); ?>" required 
                               class="w-full p-3 border rounded-lg"
                               placeholder="John">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($edit_barber['last_name']); ?>" required 
                               class="w-full p-3 border rounded-lg"
                               placeholder="Doe">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Hired Date *</label>
                        <input type="date" name="hired_date" value="<?php echo $edit_barber['hired_date']; ?>" required 
                               class="w-full p-3 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Section</label>
                        <select name="section" class="w-full p-3 border rounded-lg">
                            <option value="A" <?php echo ($edit_barber['section'] == 'A') ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo ($edit_barber['section'] == 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo ($edit_barber['section'] == 'C') ? 'selected' : ''; ?>>C</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Contact Number</label>
                    <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($edit_barber['contact_number']); ?>" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0912 345 6789">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Wage/Day (₱) *</label>
                    <input type="number" name="wage_per_day" value="<?php echo $edit_barber['wage_per_day']; ?>" step="0.01" min="0" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="500.00">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" class="mr-2" 
                           <?php echo ($edit_barber['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="is_active" class="text-gray-700">Active Barber</label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="update_barber" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Update Barber
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').classList.remove('flex');
}

function deactivateBarber(id) {
    if (confirm('Are you sure you want to deactivate this barber?\n\nDeactivated barbers can still be activated later and their transaction history will be preserved.')) {
        window.location.href = 'barbers.php?delete_barber=' + id;
    }
}

function deleteBarber(id, isActive) {
    if (isActive) {
        alert('Cannot delete active barber. Please deactivate first.');
        return;
    }
    
    if (confirm('⚠️ WARNING: This action cannot be undone!\n\nAre you sure you want to PERMANENTLY DELETE this barber?\n\nThis will remove the barber and all associated data from the system.')) {
        window.location.href = 'barbers.php?permanent_delete=' + id;
    }
}
function activateBarber(id) {
    if (confirm('Are you sure you want to activate this barber?')) {
        window.location.href = 'barbers.php?activate_barber=' + id;
    }
}

// Initialize edit modal if edit_barber is set
<?php if ($edit_barber): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
});
<?php endif; ?>

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
    // Redirect back to barbers.php without edit parameter
    window.location.href = 'barbers.php';
}
</script>

<?php require_once 'includes/footer.php'; ?>