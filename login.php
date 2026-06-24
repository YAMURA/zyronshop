<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($username, $password)) {
        if (isAdmin()) {
            header('Location: admin/index.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: radial-gradient(circle at 20% 15%, rgba(147,51,234,0.22), rgba(0,0,0,0) 42%),
                        linear-gradient(135deg, #07070a 0%, #0b0b12 45%, #07070a 100%);
            min-height: 100vh;
        }
        .glass-card {
            backdrop-filter: blur(18px);
            background: linear-gradient(180deg, rgba(22,16,40,0.62), rgba(14,12,24,0.50));
            border: 1px solid rgba(147,51,234,0.22);
            border-radius: 28px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #9333ea, #3b82f6);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }
        .input-field {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #9333ea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(147,51,234,0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="glass-card p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-shop text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Welcome Back</h2>
                <p class="text-gray-400 text-sm">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-400 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm mb-2">Username or Email</label>
                    <input type="text" name="username" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm mb-2">Password</label>
                    <input type="password" name="password" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-white font-semibold">
                    <i class="bi bi-box-arrow-in-right mr-2"></i> Sign In
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="register.php" class="text-purple-400 hover:text-purple-300 text-sm">Don't have an account? Register</a>
            </div>
        </div>
    </div>
</body>
</html>