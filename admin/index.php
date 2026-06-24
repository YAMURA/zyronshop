<?php
require_once '../includes/functions.php';
requireAdmin();

// Get statistics
$db = Database::getInstance();
$result = $db->getConnection()->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$pendingOrders = $result->fetch_assoc()['count'];

$result = $db->getConnection()->query("SELECT COUNT(*) as count FROM deposits WHERE status = 'pending'");
$pendingDeposits = $result->fetch_assoc()['count'];

$result = $db->getConnection()->query("SELECT COUNT(*) as count FROM creator_applications WHERE status = 'pending'");
$pendingCreators = $result->fetch_assoc()['count'];

$result = $db->getConnection()->query("SELECT COUNT(*) as count FROM reseller_applications WHERE status = 'pending'");
$pendingResellers = $result->fetch_assoc()['count'];

$result = $db->getConnection()->query("SELECT SUM(amount) as total FROM orders WHERE status = 'approved'");
$totalRevenue = $result->fetch_assoc()['total'] ?? 0;

$result = $db->getConnection()->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: radial-gradient(circle at 20% 15%, rgba(147,51,234,0.15), rgba(0,0,0,0) 42%),
                        linear-gradient(135deg, #07070a 0%, #0b0b12 45%, #07070a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(10,14,28,0.6);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(147,51,234,0.16);
            border-radius: 24px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #9333ea, #3b82f6);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: rgba(10,14,28,0.95);
            backdrop-filter: blur(18px);
            border-right: 1px solid rgba(147,51,234,0.2);
            padding: 20px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            color: #9ca3af;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(147,51,234,0.2);
            color: #c084fc;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg flex items-center justify-center">
                <i class="bi bi-shop text-white text-sm"></i>
            </div>
            <span class="text-white font-bold">Admin Panel</span>
        </div>
        <a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="orders.php"><i class="bi bi-receipt"></i> Orders</a>
        <a href="deposits.php"><i class="bi bi-wallet2"></i> Deposits</a>
        <a href="products.php"><i class="bi bi-box"></i> Products</a>
        <a href="users.php"><i class="bi bi-people"></i> Users</a>
        <a href="creators.php"><i class="bi bi-camera-reels"></i> Creator Apps</a>
        <a href="resellers.php"><i class="bi bi-people"></i> Reseller Apps</a>
        <a href="feedback.php"><i class="bi bi-star"></i> Feedback</a>
        <a href="announcements.php"><i class="bi bi-megaphone"></i> Announcements</a>
        <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
        <hr class="border-white/10 my-4">
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="flex justify-between items-center mb-6">
            <button class="md:hidden text-white text-2xl" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="text-2xl font-bold text-white">Admin Dashboard</h1>
            <div class="text-right">
                <div class="text-white text-sm"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                <div class="text-gray-400 text-xs">Administrator</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div><p class="text-gray-400 text-sm">Pending Orders</p><p class="text-2xl font-bold text-white"><?php echo $pendingOrders; ?></p></div>
                    <i class="bi bi-receipt text-3xl text-orange-400"></i>
                </div>
                <a href="orders.php" class="text-purple-400 text-sm mt-2 inline-block">View All →</a>
            </div>
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div><p class="text-gray-400 text-sm">Pending Deposits</p><p class="text-2xl font-bold text-white"><?php echo $pendingDeposits; ?></p></div>
                    <i class="bi bi-wallet2 text-3xl text-green-400"></i>
                </div>
                <a href="deposits.php" class="text-purple-400 text-sm mt-2 inline-block">View All →</a>
            </div>
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div><p class="text-gray-400 text-sm">Creator Apps</p><p class="text-2xl font-bold text-white"><?php echo $pendingCreators; ?></p></div>
                    <i class="bi bi-camera-reels text-3xl text-pink-400"></i>
                </div>
                <a href="creators.php" class="text-purple-400 text-sm mt-2 inline-block">View All →</a>
            </div>
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div><p class="text-gray-400 text-sm">Reseller Apps</p><p class="text-2xl font-bold text-white"><?php echo $pendingResellers; ?></p></div>
                    <i class="bi bi-people text-3xl text-blue-400"></i>
                </div>
                <a href="resellers.php" class="text-purple-400 text-sm mt-2 inline-block">View All →</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-white mb-4">Quick Stats</h2>
                <div class="space-y-3">
                    <div class="flex justify-between p-3 rounded-xl bg-white/5">
                        <span class="text-gray-400">Total Revenue</span>
                        <span class="text-green-400 font-bold">₱<?php echo number_format($totalRevenue, 2); ?></span>
                    </div>
                    <div class="flex justify-between p-3 rounded-xl bg-white/5">
                        <span class="text-gray-400">Total Users</span>
                        <span class="text-white font-bold"><?php echo $totalUsers; ?></span>
                    </div>
                </div>
            </div>
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-white mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="products.php?action=add" class="btn-primary text-center py-2 rounded-xl text-white">Add Product</a>
                    <a href="announcements.php" class="btn-primary text-center py-2 rounded-xl text-white">Post Announcement</a>
                    <a href="orders.php" class="bg-white/10 text-center py-2 rounded-xl text-white hover:bg-white/20">View Orders</a>
                    <a href="deposits.php" class="bg-white/10 text-center py-2 rounded-xl text-white hover:bg-white/20">View Deposits</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>