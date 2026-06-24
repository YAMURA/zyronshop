<?php
require_once 'includes/functions.php';
requireLogin();

$product_id = $_GET['product_id'] ?? 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: products.php');
    exit;
}

$finalPrice = $product['price'] * (1 - $product['discount'] / 100);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegram = $_POST['telegram'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $receipt = uploadFile($_FILES['receipt'], 'receipts/');
        
        if ($receipt) {
            $orderId = createOrder(
                $_SESSION['user_id'],
                $_SESSION['username'],
                $telegram,
                $product['name'],
                $finalPrice,
                $payment_method,
                $receipt
            );
            
            if ($orderId) {
                $success = "Order placed successfully! Order ID: $orderId";
            } else {
                $error = "Failed to place order. Please try again.";
            }
        } else {
            $error = "Failed to upload receipt.";
        }
    } else {
        $error = "Please upload your payment receipt.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
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
        .payment-details {
            background: rgba(0,0,0,0.3);
            border-radius: 16px;
            padding: 16px;
        }
    </style>
</head>
<body class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white">Checkout</h1>
            <a href="products.php" class="text-purple-400 hover:text-purple-300">← Back to Products</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-white mb-4">Order Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between p-3 rounded-xl bg-white/5">
                        <span class="text-gray-400">Product</span>
                        <span class="text-white"><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                    <div class="flex justify-between p-3 rounded-xl bg-white/5">
                        <span class="text-gray-400">Original Price</span>
                        <span class="text-gray-400 line-through">₱<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <?php if ($product['discount'] > 0): ?>
                    <div class="flex justify-between p-3 rounded-xl bg-white/5">
                        <span class="text-gray-400">Discount</span>
                        <span class="text-green-400">-<?php echo $product['discount']; ?>%</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between p-3 rounded-xl bg-purple-500/20">
                        <span class="text-white font-bold">Total Amount</span>
                        <span class="text-purple-400 font-bold">₱<?php echo number_format($finalPrice, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-white mb-4">Payment Information</h2>
                
                <?php if (isset($success)): ?>
                    <div class="mb-4 p-3 rounded-xl bg-green-500/20 text-green-400 text-center"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="mb-4 p-3 rounded-xl bg-red-500/20 text-red-400 text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Your Telegram Username</label>
                        <input type="text" name="telegram" required placeholder="@username" class="input-field w-full rounded-xl p-3 text-white">
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Payment Method</label>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <button type="button" onclick="selectMethod('gcash')" class="method-btn p-3 rounded-xl bg-green-500/20 border border-green-500/30 text-green-400">
                                <i class="bi bi-phone"></i> GCash
                            </button>
                            <button type="button" onclick="selectMethod('binance')" class="method-btn p-3 rounded-xl bg-yellow-500/20 border border-yellow-500/30 text-yellow-400">
                                <i class="bi bi-currency-bitcoin"></i> Binance
                            </button>
                        </div>
                        <input type="hidden" name="payment_method" id="payment_method" required>
                    </div>

                    <div id="paymentDetails" class="payment-details hidden">
                        <p class="text-white text-sm mb-2">Send payment to:</p>
                        <p id="accountDetails" class="text-purple-400 font-mono"></p>
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Upload Payment Proof</label>
                        <input type="file" name="receipt" accept="image/*" required class="w-full rounded-xl p-2 bg-white/5 border border-white/10 text-white">
                        <p class="text-xs text-gray-500 mt-1">Upload screenshot of your payment transaction</p>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3 rounded-xl text-white font-semibold">
                        <i class="bi bi-check-circle mr-2"></i> Place Order
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectMethod(method) {
            document.getElementById('payment_method').value = method;
            const details = document.getElementById('paymentDetails');
            const accountDetails = document.getElementById('accountDetails');
            
            if (method === 'gcash') {
                accountDetails.innerHTML = 'GCash Number: <strong><?php echo GCASH_NUMBER; ?></strong><br>Name: <strong><?php echo GCASH_NAME; ?></strong>';
            } else {
                accountDetails.innerHTML = 'Binance Wallet: <strong><?php echo BINANCE_WALLET; ?></strong><br>Network: <strong><?php echo BINANCE_NETWORK; ?></strong>';
            }
            details.classList.remove('hidden');
            
            document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('bg-opacity-30'));
            event.target.classList.add('bg-opacity-30');
        }
    </script>
</body>
</html>