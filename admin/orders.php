<?php
require_once '../includes/functions.php';
requireAdmin();

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    updateOrderStatus($_POST['order_id'], $_POST['status']);
    header('Location: orders.php');
    exit;
}

$orders = getOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: radial-gradient(circle at 20% 15%, rgba(147,51,234,0.15), rgba(0,0,0,0) 42%),
                        linear-gradient(135deg, #07070a 0%, #0b0b12 45%, #07070a 100%);
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
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.05); }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-pending { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .status-approved { background: rgba(16,185,129,0.2); color: #34d399; }
        .status-declined { background: rgba(239,68,68,0.2); color: #f87171; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Manage Orders</h1>
            <a href="index.php" class="text-purple-400 hover:text-purple-300">← Back to Dashboard</a>
        </div>

        <div class="glass-card p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left p-3 text-gray-400">Order ID</th>
                            <th class="text-left p-3 text-gray-400">Customer</th>
                            <th class="text-left p-3 text-gray-400">Product</th>
                            <th class="text-left p-3 text-gray-400">Amount</th>
                            <th class="text-left p-3 text-gray-400">Method</th>
                            <th class="text-left p-3 text-gray-400">Status</th>
                            <th class="text-left p-3 text-gray-400">Date</th>
                            <th class="text-left p-3 text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr class="border-b border-white/5">
                            <td class="p-3 text-white"><?php echo $order['order_id']; ?></td>
                            <td class="p-3 text-white"><?php echo htmlspecialchars($order['username']); ?></td>
                            <td class="p-3 text-white"><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td class="p-3 text-white">₱<?php echo number_format($order['amount'], 2); ?></td>
                            <td class="p-3 text-white"><?php echo strtoupper($order['payment_method']); ?></td>
                            <td class="p-3"><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo strtoupper($order['status']); ?></span></td>
                            <td class="p-3 text-gray-400"><?php echo date('M d, Y', strtotime($order['timestamp'])); ?></td>
                            <td class="p-3">
                                <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="status" value="approved" class="bg-green-500/20 text-green-400 px-3 py-1 rounded-lg text-sm hover:bg-green-500/30">Approve</button>
                                    <button type="submit" name="status" value="declined" class="bg-red-500/20 text-red-400 px-3 py-1 rounded-lg text-sm hover:bg-red-500/30">Decline</button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-500 text-sm">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>