<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$orders = getOrders($userId);
$pendingOrders = array_filter($orders, function($o) { return $o['status'] === 'pending'; });
$approvedOrders = array_filter($orders, function($o) { return $o['status'] === 'approved'; });
$totalSpent = array_sum(array_column($approvedOrders, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Dashboard</title>
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
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .status-approved { background: rgba(16,185,129,0.2); color: #34d399; }
        .status-declined { background: rgba(239,68,68,0.2); color: #f87171; }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-black/50 backdrop-blur-lg border-b border-white/10 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg flex items-center justify-center">
                    <i class="bi bi-shop text-white text-sm"></i>
                </div>
                <span class="text-white font-bold"><?php echo SITE_NAME; ?></span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-white">Balance: ₱<?php echo number_format($_SESSION['balance'], 2); ?></span>
                <a href="logout.php" class="text-red-400 hover:text-red-300">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="glass-card p-6 mb-6">
            <h1 class="text-2xl font-bold text-white">Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>! 👋</h1>
            <p class="text-gray-400">Ready to shop? Check out our latest products.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-400 text-sm">Total Orders</p>
                        <p class="text-2xl font-bold text-white"><?php echo count($orders); ?></p>
                    </div>
                    <i class="bi bi-bag-check text-3xl text-purple-400"></i>
                </div>
            </div>
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-400 text-sm">Total Spent</p>
                        <p class="text-2xl font-bold text-white">₱<?php echo number_format($totalSpent, 2); ?></p>
                    </div>
                    <i class="bi bi-wallet2 text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="glass-card p-5">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-400 text-sm">Active Orders</p>
                        <p class="text-2xl font-bold text-white"><?php echo count($pendingOrders); ?></p>
                    </div>
                    <i class="bi bi-hourglass-split text-3xl text-yellow-400"></i>
                </div>
            </div>
        </div>

        <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-white mb-4">Recent Orders</h2>
            <div class="space-y-3">
                <?php if (empty($orders)): ?>
                    <p class="text-gray-400 text-center py-8">No orders yet. Start shopping!</p>
                <?php else: ?>
                    <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                        <div class="flex justify-between items-center p-3 rounded-xl bg-white/5">
                            <div>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($order['product_name']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo date('M d, Y', strtotime($order['timestamp'])); ?></p>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo strtoupper($order['status']); ?></span>
                                <p class="text-white text-sm mt-1">₱<?php echo number_format($order['amount'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <a href="products.php" class="glass-card p-4 text-center hover:border-purple-500/30 transition">
                <i class="bi bi-shop text-2xl text-purple-400 mb-2 block"></i>
                <span class="text-white text-sm">Shop Now</span>
            </a>
            <a href="deposit.php" class="glass-card p-4 text-center hover:border-purple-500/30 transition">
                <i class="bi bi-plus-circle text-2xl text-green-400 mb-2 block"></i>
                <span class="text-white text-sm">Deposit</span>
            </a>
            <a href="orders.php" class="glass-card p-4 text-center hover:border-purple-500/30 transition">
                <i class="bi bi-receipt text-2xl text-blue-400 mb-2 block"></i>
                <span class="text-white text-sm">My Orders</span>
            </a>
            <a href="support.php" class="glass-card p-4 text-center hover:border-purple-500/30 transition">
                <i class="bi bi-chat-dots text-2xl text-pink-400 mb-2 block"></i>
                <span class="text-white text-sm">Support</span>
            </a>
        </div>
    </main>
</body>
</html>