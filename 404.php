<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - LANTIN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="mb-8">
            <h1 class="text-9xl font-bold text-gray-800">404</h1>
            <h2 class="text-3xl font-semibold text-gray-600 mt-4">Page Not Found</h2>
            <p class="text-gray-500 mt-2 max-w-md mx-auto">
                The page you are looking for doesn't exist or has been moved.
            </p>
        </div>
        
        <div class="space-x-4">
            <a href="dashboard.php" class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:opacity-90 transition">
                <i class="fas fa-home mr-2"></i>Go to Dashboard
            </a>
            <a href="javascript:history.back()" class="inline-block bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Go Back
            </a>
        </div>
        
        <div class="mt-12 text-gray-400">
            <p>LANTIN Barbershop Management System</p>
            <p class="text-sm mt-2">© <?php echo date('Y'); ?> All rights reserved</p>
        </div>
    </div>
</body>
</html>