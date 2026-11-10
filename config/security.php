<?php
// Security configuration and validation functions

function get_app_key() {
    $configured_key = getenv('APP_KEY');
    if (!empty($configured_key)) {
        return $configured_key;
    }

    if (defined('APP_KEY')) {
        return APP_KEY;
    }

    return 'change-this-app-key-to-a-strong-random-secret';
}

// Encryption key (store in environment variable in production)
define('APP_KEY', get_app_key());
define('ENCRYPTION_KEY', substr(hash('sha256', APP_KEY), 0, 32));
define('ENCRYPTION_METHOD', 'AES-256-CBC');

function normalize_email($email) {
    return strtolower(trim((string) $email));
}

function hash_identifier($value) {
    return hash_hmac('sha256', normalize_email((string) $value), APP_KEY);
}

// Encrypt sensitive data
function encrypt_data($data) {
    if (empty($data)) return '';

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt((string) $data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);

    return base64_encode($encrypted . '::' . $iv);
}

// Decrypt sensitive data
function decrypt_data($encrypted_data) {
    if (empty($encrypted_data)) return '';

    $payload = base64_decode((string) $encrypted_data, true);
    if ($payload === false) {
        return '';
    }

    $parts = explode('::', $payload, 2);
    if (count($parts) !== 2) {
        return '';
    }

    [$encrypted_data, $iv] = $parts;
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}

// Sanitize input
function sanitize_input($data) {
    $data = trim((string) $data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var(normalize_email($email), FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone (11 digits)
function validate_phone($phone) {
    return preg_match('/^\d{11}$/', (string) $phone) === 1;
}

// Validate username (4-20 chars, letters, numbers, underscore)
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{4,20}$/', (string) $username) === 1;
}

// Validate password strength
function validate_password($password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, and 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', (string) $password) === 1;
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

// Rate limiting function
function check_rate_limit($key, $limit = 5, $timeout = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $current_time = time();
    $attempts = $_SESSION['rate_limit'][$key] ?? [];

    // Remove old attempts
    $attempts = array_filter($attempts, function($time) use ($current_time, $timeout) {
        return $time > $current_time - $timeout;
    });

    if (count($attempts) >= $limit) {
        return false; // Rate limit exceeded
    }

    $attempts[] = $current_time;
    $_SESSION['rate_limit'][$key] = $attempts;
    return true;
}

// XSS protection headers
function set_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; connect-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
}
?>