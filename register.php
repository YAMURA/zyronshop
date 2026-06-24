<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($fullname)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        if (registerUser($username, $email, $password, $fullname)) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = 'Username or email already exists';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Register</title>
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
                    <i class="bi bi-person-plus text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Create Account</h2>
                <p class="text-gray-400 text-sm">Join Cluddy Shop today</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-400 text-center"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-4 p-3 rounded-xl bg-green-500/20 text-green-400 text-center"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="block text-gray-300 text-sm mb-2">Full Name</label>
                    <input type="text" name="fullname" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <div class="mb-3">
                    <label class="block text-gray-300 text-sm mb-2">Email</label>
                    <input type="email" name="email" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <div class="mb-3">
                    <label class="block text-gray-300 text-sm mb-2">Username</label>
                    <input type="text" name="username" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <div class="mb-3">
                    <label class="block text-gray-300 text-sm mb-2">Password</label>
                    <input type="password" name="password" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="input-field w-full rounded-xl p-3 text-white">
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-white font-semibold">
                    <i class="bi bi-person-plus mr-2"></i> Register
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="login.php" class="text-purple-400 hover:text-purple-300 text-sm">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>