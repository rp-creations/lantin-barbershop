<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission and redirects BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    // Handle Add Item
    if (isset($_POST['add_item'])) {
        $item_number = trim($_POST['item_number'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $quantity = $_POST['quantity'] ?? 0;
        $reorder_level = $_POST['reorder_level'] ?? 5;
        $unit_price = $_POST['unit_price'] ?? 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($item_number)) {
            $errors[] = 'Item number is required';
        }
        if (empty($name)) {
            $errors[] = 'Item name is required';
        }
        if (empty($quantity) || $quantity < 0) {
            $errors[] = 'Valid quantity is required';
        }
        if (empty($reorder_level) || $reorder_level < 0) {
            $errors[] = 'Valid reorder level is required';
        }
        
        if (empty($errors)) {
            try {
                // Check if item number already exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE item_number = ?");
                $checkStmt->execute([$item_number]);
                if ($checkStmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Item number already exists!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO inventory (item_number, name, brand, quantity, reorder_level, unit_price) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$item_number, $name, $brand, $quantity, $reorder_level, $unit_price]);
                    
                    addLog('Added inventory item', "Item: $name, Quantity: $quantity");
                    $_SESSION['success'] = "Item added successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding item: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Handle Update Item
    if (isset($_POST['update_item'])) {
        $item_id = $_POST['item_id'] ?? 0;
        $item_number = trim($_POST['item_number'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $quantity = $_POST['quantity'] ?? 0;
        $reorder_level = $_POST['reorder_level'] ?? 5;
        $unit_price = $_POST['unit_price'] ?? 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($item_number)) {
            $errors[] = 'Item number is required';
        }
        if (empty($name)) {
            $errors[] = 'Item name is required';
        }
        if (empty($quantity) || $quantity < 0) {
            $errors[] = 'Valid quantity is required';
        }
        if (empty($reorder_level) || $reorder_level < 0) {
            $errors[] = 'Valid reorder level is required';
        }
        
        if (empty($errors)) {
            try {
                // Check if item number already exists (excluding current item)
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE item_number = ? AND id != ?");
                $checkStmt->execute([$item_number, $item_id]);
                if ($checkStmt->fetchColumn() > 0) {
                    $_SESSION['error'] = "Item number already exists!";
                } else {
                    $stmt = $pdo->prepare("UPDATE inventory SET item_number = ?, name = ?, brand = ?, quantity = ?, reorder_level = ?, unit_price = ? WHERE id = ?");
                    $stmt->execute([$item_number, $name, $brand, $quantity, $reorder_level, $unit_price, $item_id]);
                    
                    addLog('Updated inventory item', "Item ID: $item_id, Item: $name");
                    $_SESSION['success'] = "Item updated successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating item: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    // Handle restock
    if (isset($_POST['restock_item'])) {
        $item_id = $_POST['restock_id'] ?? 0;
        $restock_quantity = $_POST['restock_quantity'] ?? 0;
        
        try {
            // Get current quantity
            $stmt = $pdo->prepare("SELECT quantity, name FROM inventory WHERE id = ?");
            $stmt->execute([$item_id]);
            $current = $stmt->fetch();
            
            if ($current) {
                $new_quantity = $current['quantity'] + $restock_quantity;
                
                $updateStmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $updateStmt->execute([$new_quantity, $item_id]);
                
                addLog('Restocked inventory', "Item: {$current['name']}, Added: $restock_quantity, New Total: $new_quantity");
                $_SESSION['success'] = "Item restocked successfully!";
            } else {
                $_SESSION['error'] = "Item not found!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error restocking item: " . $e->getMessage();
        }
        
        header('Location: inventory.php');
        exit();
    }
}

// Handle item deletion
if (isset($_GET['delete_item'])) {
    require_once 'config/database.php';
    
    $item_id = $_GET['delete_item'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        
        addLog('Deleted inventory item', "Item ID: $item_id");
        $_SESSION['success'] = "Item deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting item: " . $e->getMessage();
    }
    
    header('Location: inventory.php');
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

// Get all inventory items
try {
    require_once 'config/database.php';
    $inventory = $pdo->query("SELECT * FROM inventory ORDER BY item_number")->fetchAll();
} catch (PDOException $e) {
    $inventory = [];
    $error = "Error loading inventory: " . $e->getMessage();
}

// Get item details for editing
$edit_item = null;
if (isset($_GET['edit_item'])) {
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$_GET['edit_item']]);
        $edit_item = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error loading item details: " . $e->getMessage();
    }
}
?>

<div class="max-w-7xl my-5 sm:px-6 lg:px-8 fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <h2 class="text-2xl font-bold mb-4 md:mb-0">Inventory Management</h2>
        <button onclick="openAddModal()" class="btn-primary text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Add Item
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
    
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="inventory-table w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4 text-left">Number</th>
                        <th class="p-4 text-left">Name</th>
                        <th class="p-4 text-left">Brand</th>
                        <th class="p-4 text-left">Quantity</th>
                        <th class="p-4 text-left">Reorder Level</th>
                        <th class="p-4 text-left">Unit Price</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($inventory) > 0): ?>
                        <?php foreach ($inventory as $item): 
                            $low_stock = $item['quantity'] <= $item['reorder_level'];
                            $out_of_stock = $item['quantity'] == 0;
                            $status_color = $out_of_stock ? 'bg-red-100 text-red-800' : ($low_stock ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                            $status_text = $out_of_stock ? 'Out of Stock' : ($low_stock ? 'Low Stock' : 'In Stock');
                            $row_color = $out_of_stock ? 'bg-red-50' : ($low_stock ? 'bg-yellow-50' : '');
                        ?>
                        <tr class="border-b hover:bg-gray-50 <?php echo $row_color; ?>">
                            <td class="p-4 font-semibold"><?php echo htmlspecialchars($item['item_number']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($item['brand']); ?></td>
                            <td class="p-4 font-semibold <?php echo $out_of_stock ? 'text-red-600' : ($low_stock ? 'text-yellow-600' : 'text-green-600'); ?>">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="p-4"><?php echo $item['reorder_level']; ?></td>
                            <td class="p-4">₱<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex space-x-2">
                                    <a href="?edit_item=<?php echo $item['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 p-1 hover:bg-blue-50 rounded">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                  
                                    <button onclick="deleteItem(<?php echo $item['id']; ?>)" 
                                            class="text-red-600 hover:text-red-800 p-1 hover:bg-red-50 rounded">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
                                <i class="fas fa-boxes text-gray-300 text-4xl mb-3"></i>
                                <p>No inventory items found</p>
                                <button onclick="openAddModal()" class="mt-4 btn-primary text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-plus mr-2"></i>Add Your First Item
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Add New Item</h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Item Number *</label>
                    <input type="text" name="item_number" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., 01, 02, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Talc Powder, Razor Blade, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Brand</label>
                    <input type="text" name="brand" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Carrie, Dorco, etc.">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" min="0" required 
                               class="w-full p-3 border rounded-lg"
                               value="0">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Reorder Level</label>
                        <input type="number" name="reorder_level" min="0" 
                               class="w-full p-3 border rounded-lg"
                               value="5">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Unit Price (₱)</label>
                    <input type="number" name="unit_price" step="0.01" min="0" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0.00">
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeAddModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="add_item" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Edit Item</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if ($edit_item): ?>
        <form method="POST">
            <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Item Number *</label>
                    <input type="text" name="item_number" value="<?php echo htmlspecialchars($edit_item['item_number']); ?>" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., 01, 02, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_item['name']); ?>" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Talc Powder, Razor Blade, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Brand</label>
                    <input type="text" name="brand" value="<?php echo htmlspecialchars($edit_item['brand']); ?>" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Carrie, Dorco, etc.">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" value="<?php echo $edit_item['quantity']; ?>" min="0" required 
                               class="w-full p-3 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Reorder Level</label>
                        <input type="number" name="reorder_level" value="<?php echo $edit_item['reorder_level']; ?>" min="0" 
                               class="w-full p-3 border rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Unit Price (₱)</label>
                    <input type="number" name="unit_price" value="<?php echo $edit_item['unit_price']; ?>" step="0.01" min="0" 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0.00">
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="update_item" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Update Item
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Restock Modal -->
<div id="restockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Restock Item</h3>
            <button onclick="closeRestockModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="restock_id" id="restockItemId">
            
            <div class="space-y-4">
                <div>
                    <p class="text-gray-700 mb-2">Item: <span id="restockItemName" class="font-semibold"></span></p>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Current Quantity</label>
                    <input type="number" id="restockCurrentQuantity" 
                           class="w-full p-3 border rounded-lg bg-gray-50" readonly>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Add Quantity *</label>
                    <input type="number" name="restock_quantity" id="restockQuantity" min="1" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="Number of items to add">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">New Total Quantity</label>
                    <input type="number" id="restockNewQuantity" 
                           class="w-full p-3 border rounded-lg bg-gray-50" readonly>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeRestockModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="restock_item" 
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-box mr-2"></i>Restock
                </button>
            </div>
        </form>
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

function restockItem(id) {
    // Get current item details via AJAX
    fetch('inventory.php?get_item=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('restockItemId').value = data.item.id;
                document.getElementById('restockItemName').textContent = data.item.name;
                document.getElementById('restockCurrentQuantity').value = data.item.quantity;
                document.getElementById('restockQuantity').value = '';
                document.getElementById('restockNewQuantity').value = data.item.quantity;
                
                document.getElementById('restockModal').classList.remove('hidden');
                document.getElementById('restockModal').classList.add('flex');
            } else {
                alert('Error loading item details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading item details');
        });
}

function closeRestockModal() {
    document.getElementById('restockModal').classList.add('hidden');
    document.getElementById('restockModal').classList.remove('flex');
}

function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        window.location.href = 'inventory.php?delete_item=' + id;
    }
}

// Calculate new quantity when restock quantity changes
document.getElementById('restockQuantity').addEventListener('input', function() {
    const current = parseInt(document.getElementById('restockCurrentQuantity').value) || 0;
    const add = parseInt(this.value) || 0;
    document.getElementById('restockNewQuantity').value = current + add;
});

// Initialize edit modal if edit_item is set
<?php if ($edit_item): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
});
<?php endif; ?>

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
    // Redirect back to inventory.php without edit parameter
    window.location.href = 'inventory.php';
}
</script>

<?php 
// Handle AJAX request for getting item details
if (isset($_GET['get_item'])) {
    require_once 'config/database.php';
    
    $item_id = $_GET['get_item'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
?>

<?php require_once 'includes/footer.php'; ?>