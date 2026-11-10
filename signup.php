<?php
session_start();
require_once 'config/database.php';
require_once 'config/security.php';

set_security_headers();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $position = $_POST['position'] ?? '';
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate all fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    
    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    } else {
        // Check email uniqueness
        $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $emailCheck->execute([$email]);
        if ($emailCheck->fetchColumn() > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // Phone validation (11 digits)
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!validate_phone($phone)) {
        $errors[] = "Phone must be 11 digits";
    }
    
    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!validate_username($username)) {
        $errors[] = "Username must be 4-20 characters (letters, numbers, underscore)";
    } else {
        // Check username uniqueness
        $userCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $userCheck->execute([$username]);
        if ($userCheck->fetchColumn() > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (!validate_password($password)) {
        $errors[] = "Password must be 8+ chars with uppercase, lowercase, and number";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $email_lookup = hash_identifier($email);
            $encrypted_phone = encrypt_data($phone);

            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $verification_token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, position, username, password, email_verified, verification_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())");

            $stmt->execute([
                $first_name,
                $last_name,
                $email_lookup,
                $encrypted_phone,
                $position,
                $username,
                $password_hash,
                $verification_token
            ]);
            
            // Send verification email (simulated)
            // send_verification_email($email, $verification_token);
            
            addLog('User registration', "New user registered: $username");
            
            $_SESSION['success_message'] = "Account created successfully! You can now sign in.";
            header('Location: login.php');
            exit();
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - LANTIN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        .password-strength-meter {
            height: 4px;
            border-radius: 2px;
            margin-top: 4px;
            transition: all 0.3s;
        }
        .strength-0 { width: 0%; background: #ef4444; }
        .strength-1 { width: 25%; background: #ef4444; }
        .strength-2 { width: 50%; background: #f59e0b; }
        .strength-3 { width: 75%; background: #10b981; }
        .strength-4 { width: 100%; background: #10b981; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">LANTIN</h1>
            <p class="text-gray-600 mt-2 text-sm">Professional Barbershop Management</p>
        </div>
        
        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-900">Create Account</h2>
                <p class="text-gray-600 text-sm mt-1">Join our professional management system</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-800 mb-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span class="font-medium">Please fix the following:</span>
                    </div>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="signupForm" class="space-y-5">
                <!-- Name Fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <input type="text" name="first_name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input type="text" name="last_name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required 
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="you@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               onblur="checkEmail(this.value)">
                    </div>
                    <p id="emailError" class="text-red-500 text-xs mt-2 hidden"></p>
                </div>
                
                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <div class="relative">
                        <i class="fas fa-phone absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="tel" name="phone" required 
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="09123456789"
                               maxlength="11"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Position -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                    <select name="position" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Select Position</option>
                        <option value="Manager" <?php echo ($_POST['position'] ?? '') == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="Supervisor" <?php echo ($_POST['position'] ?? '') == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                        <option value="Staff" <?php echo ($_POST['position'] ?? '') == 'Staff' ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
                
                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="username" required 
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="4-20 characters"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               onblur="checkUsername(this.value)">
                    </div>
                    <p id="usernameError" class="text-red-500 text-xs mt-2 hidden"></p>
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required 
                               id="password"
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••"
                               onkeyup="checkPasswordStrength(this.value)">
                        <i class="fas fa-eye absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer" 
                           onclick="togglePassword('password', this)"></i>
                    </div>
                    <div class="password-strength-meter" id="passwordStrength"></div>
                    <p class="text-xs text-gray-500 mt-2">
                        Must contain: uppercase, lowercase, and number (8+ chars)
                    </p>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="confirm_password" required 
                               id="confirm_password"
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••">
                        <i class="fas fa-eye absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer" 
                           onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <p id="passwordMatch" class="text-xs mt-2 hidden"></p>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-gray-900 to-gray-800 text-white py-3 px-4 rounded-lg font-medium hover:from-gray-800 hover:to-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>
            
            <!-- Login Link -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-gray-600 text-sm">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-gray-900 hover:text-blue-600 transition">Sign in</a>
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-gray-500 text-xs mt-6">
            © <?php echo date('Y'); ?> LANTIN. All rights reserved.
        </p>
    </div>
    
    <script>
    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        const meter = document.getElementById('passwordStrength');
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        meter.className = 'password-strength-meter strength-' + strength;
    }
    
    // Toggle password visibility
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Check password match
    document.getElementById('confirm_password').addEventListener('keyup', function() {
        const password = document.getElementById('password').value;
        const confirm = this.value;
        const matchText = document.getElementById('passwordMatch');
        
        if (confirm.length > 0) {
            if (password === confirm) {
                matchText.textContent = '✓ Passwords match';
                matchText.className = 'text-xs mt-2 text-green-600';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.className = 'text-xs mt-2 text-red-600';
            }
            matchText.classList.remove('hidden');
        } else {
            matchText.classList.add('hidden');
        }
    });
    
    // Check username availability
    function checkUsername(username) {
        if (username.length < 4) return;
        
        const errorElement = document.getElementById('usernameError');
        errorElement.classList.add('hidden');
        
        fetch('api/check_username.php?username=' + encodeURIComponent(username))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorElement.textContent = 'Username is already taken';
                    errorElement.classList.remove('hidden');
                }
            })
            .catch(() => {
                errorElement.textContent = 'Unable to check username';
                errorElement.classList.remove('hidden');
            });
    }
    
    // Check email availability
    function checkEmail(email) {
        if (!email.includes('@')) return;
        
        const errorElement = document.getElementById('emailError');
        errorElement.classList.add('hidden');
        
        fetch('api/check_email.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorElement.textContent = 'Email is already registered';
                    errorElement.classList.remove('hidden');
                }
            })
            .catch(() => {
                errorElement.textContent = 'Unable to check email';
                errorElement.classList.remove('hidden');
            });
    }
    
    // Form validation
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const phone = document.querySelector('input[name="phone"]').value;
        const password = document.getElementById('password').value;
        
        // Phone validation
        if (!/^\d{11}$/.test(phone)) {
            e.preventDefault();
            alert('Phone number must be exactly 11 digits');
            return;
        }
        
        // Password strength validation
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        if (password.length < 8 || !hasUpper || !hasLower || !hasNumber) {
            e.preventDefault();
            alert('Password must be at least 8 characters with uppercase, lowercase, and number');
            return;
        }
    });
    </script>
</body>
</html>