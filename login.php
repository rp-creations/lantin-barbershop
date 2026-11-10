<?php
session_start();
require_once 'config/database.php';
require_once 'config/security.php';

set_security_headers();

$errors = [];

if (!check_rate_limit('login_attempts', 5, 300)) {
    $errors[] = "Too many login attempts. Please try again in 5 minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    }

    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required!";
    } else {
        try {
            $login_input = trim($username);
            $user = null;

            if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
                $email_hash = hash_identifier($login_input);
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email_hash]);
                $user = $stmt->fetch();
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$login_input]);
                $user = $stmt->fetch();
            }

            if ($user && password_verify($password, $user['password'])) {
                if (($user['email_verified'] ?? 1) != 1) {
                    $errors[] = "Please verify your email before logging in.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_position'] = $user['position'];
                    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['last_activity'] = time();

                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                    $updateStmt->execute([$user['id']]);

                    addLog('Login', "User logged in: " . $user['username']);
                    unset($_SESSION['rate_limit']['login_attempts']);

                    header('Location: dashboard.php');
                    exit();
                }
            } else {
                $errors[] = "Invalid username or password!";
                addLog('Failed login attempt', "Failed login for username: " . $login_input);

                if ($user) {
                    $updateStmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1, last_failed_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LANTIN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-900 tracking-tight">LANTIN</h1>
            <p class="text-gray-600 mt-2 text-sm">Professional Barbershop Management</p>
        </div>
        
        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-900">Welcome Back</h2>
                <p class="text-gray-600 text-sm mt-1">Sign in to your account</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-800 mb-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span class="font-medium">Login Failed</span>
                    </div>
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($errors[0]); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-medium">Success!</span>
                    </div>
                    <p class="text-sm text-green-700 mt-1"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username or Email</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="username" required 
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="Enter your username or email"
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <a href="forgot_password.php" class="text-sm text-gray-600 hover:text-blue-600 transition">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required 
                               id="password"
                               class="w-full pl-12 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••"
                               autocomplete="current-password">
                        <i class="fas fa-eye absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer" 
                           onclick="togglePassword()"></i>
                    </div>
                </div>
                
                <!-- Remember Me -->
                <div class="flex items-center">
                    <input type="checkbox" name="remember_me" id="remember_me" 
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="remember_me" class="ml-2 text-sm text-gray-700">Remember me</label>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-gray-900 to-gray-800 text-white py-3 px-4 rounded-lg font-medium hover:from-gray-800 hover:to-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>
            
            <!-- Divider -->
            <div class="my-6 relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">New to LANTIN?</span>
                </div>
            </div>
            
            <!-- Sign Up Link -->
            <div class="text-center">
                <a href="signup.php" 
                   class="inline-flex items-center text-gray-900 hover:text-blue-600 font-medium transition">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create new account
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-gray-500 text-xs mt-6">
            © <?php echo date('Y'); ?> LANTIN. All rights reserved.
        </p>
    </div>
    
    <script>
    // Toggle password visibility
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const icon = document.querySelector('#password + i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Focus on first field
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('input[name="username"]').focus();
    });
    </script>
</body>
</html>