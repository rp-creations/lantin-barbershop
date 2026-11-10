<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission and redirects BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database
    require_once 'config/database.php';
    
    // Handle Add Service
    if (isset($_POST['add_service'])) {
        $service_number = 'SRV' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? 0;
        
        if (empty($name) || empty($price) || $price <= 0) {
            $_SESSION['error'] = "Please fill all required fields with valid values!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (service_number, name, description, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$service_number, $name, $description, $price]);
                
                addLog('Added service', "Service: $name - ₱$price");
                $_SESSION['success'] = "Service added successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error adding service: " . $e->getMessage();
            }
        }
        
        header('Location: manage.php');
        exit();
    }
    
    // Handle Update Service
    if (isset($_POST['update_service'])) {
        $service_id = $_POST['service_id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($price) || $price <= 0 || $service_id <= 0) {
            $_SESSION['error'] = "Please fill all required fields with valid values!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $is_active, $service_id]);
                
                addLog('Updated service', "Service ID: $service_id - $name");
                $_SESSION['success'] = "Service updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating service: " . $e->getMessage();
            }
        }
        
        header('Location: manage.php');
        exit();
    }
}

// Handle service deletion
if (isset($_GET['delete_service'])) {
    require_once 'config/database.php';
    
    $service_id = $_GET['delete_service'];
    
    try {
        // Check if service has transactions
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE service_id = ?");
        $checkStmt->execute([$service_id]);
        $hasTransactions = $checkStmt->fetchColumn() > 0;
        
        if ($hasTransactions) {
            // Soft delete - mark as inactive
            $stmt = $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?");
            $stmt->execute([$service_id]);
            addLog('Deactivated service', "Service ID: $service_id");
            $_SESSION['success'] = "Service deactivated successfully!";
        } else {
            // Hard delete
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$service_id]);
            addLog('Deleted service', "Service ID: $service_id");
            $_SESSION['success'] = "Service deleted successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error removing service: " . $e->getMessage();
    }
    
    header('Location: manage.php');
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

// Get all services
try {
    require_once 'config/database.php';
    $services = $pdo->query("SELECT * FROM services ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $services = [];
    $error = "Error loading services: " . $e->getMessage();
}

// Get service details for editing
$edit_service = null;
if (isset($_GET['edit_service'])) {
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$_GET['edit_service']]);
        $edit_service = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error loading service details: " . $e->getMessage();
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 fade-in">
        <h2 class="text-2xl font-bold mb-6">Manage Services</h2>
    
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
    
    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php if (count($services) > 0): ?>
            <?php foreach ($services as $service): 
                $status_color = ($service['is_active'] ?? 1) == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                $border_color = ($service['is_active'] ?? 1) == 1 ? 'border-blue-100' : 'border-red-100';
                $category = ($service['price'] ?? 0) <= 100 ? 'Basic' : (($service['price'] ?? 0) <= 200 ? 'Premium' : 'Deluxe');
                $category_color = $category == 'Basic' ? 'bg-blue-100 text-blue-800' : 
                                 ($category == 'Premium' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800');
            ?>
            <div class="card p-6 border-2 <?php echo $border_color; ?>">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($service['name'] ?? ''); ?></h3>
                    <div class="flex space-x-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $category_color; ?>">
                            <?php echo $category; ?>
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                            <?php echo ($service['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($service['description'])): ?>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($service['description']); ?></p>
                <?php endif; ?>
                
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Price:</span>
                        <span class="font-bold text-gray-800">₱<?php echo number_format($service['price'] ?? 0, 2); ?></span>
                    </div>
                    <?php if (!empty($service['service_number'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Service ID:</span>
                        <span class="font-bold text-gray-800"><?php echo htmlspecialchars($service['service_number']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between pt-4 border-t">
                    <a href="?edit_service=<?php echo $service['id']; ?>" 
                       class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <button onclick="deleteService(<?php echo $service['id']; ?>)" 
                            class="text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-8">
                <i class="fas fa-cut text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No services found</p>
                <p class="text-gray-400 text-sm mt-2">Add your first service to get started</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add New Service Button -->
    <div class="text-center">
        <button onclick="openServiceModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-semibold">
            <i class="fas fa-plus mr-2"></i>Add New Service
        </button>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Add New Service</h3>
            <button onclick="closeAddServiceModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Service Name *</label>
                    <input type="text" name="name" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Haircut, Shave, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full p-3 border rounded-lg"
                              placeholder="Brief description of the service..."></textarea>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Price (₱) *</label>
                    <input type="number" name="price" step="0.01" min="0.01" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0.00">
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeAddServiceModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="add_service" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Add Service
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editServiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Edit Service</h3>
            <button onclick="closeEditServiceModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <?php if ($edit_service): ?>
        <form method="POST" action="">
            <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Service Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_service['name']); ?>" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="e.g., Haircut, Shave, etc.">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full p-3 border rounded-lg"
                              placeholder="Brief description of the service..."><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Price (₱) *</label>
                    <input type="number" name="price" value="<?php echo $edit_service['price']; ?>" step="0.01" min="0.01" required 
                           class="w-full p-3 border rounded-lg"
                           placeholder="0.00">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" class="mr-2" 
                           <?php echo ($edit_service['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="is_active" class="text-gray-700">Active Service</label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="closeEditServiceModal()" 
                        class="px-6 py-3 border rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" name="update_service" 
                        class="btn-primary text-white px-6 py-3 rounded-lg">
                    Update Service
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function openServiceModal() {
    document.getElementById('addServiceModal').classList.remove('hidden');
    document.getElementById('addServiceModal').classList.add('flex');
}

function closeAddServiceModal() {
    document.getElementById('addServiceModal').classList.add('hidden');
    document.getElementById('addServiceModal').classList.remove('flex');
}

function deleteService(id) {
    if (confirm('Are you sure you want to delete this service?')) {
        window.location.href = 'manage.php?delete_service=' + id;
    }
}

// Initialize edit modal if edit_service is set
<?php if ($edit_service): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editServiceModal').classList.remove('hidden');
    document.getElementById('editServiceModal').classList.add('flex');
});
<?php endif; ?>

function closeEditServiceModal() {
    document.getElementById('editServiceModal').classList.add('hidden');
    document.getElementById('editServiceModal').classList.remove('flex');
    // Redirect back to manage.php without edit parameter
    window.location.href = 'manage.php';
}
</script>

<?php require_once 'includes/footer.php'; ?>