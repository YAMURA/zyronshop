<?php
require_once 'includes/functions.php';
requireLogin();

$products = getProducts();
$categories = ['Mobile Legends', 'Call of Duty Mobile', 'NetEase Games', 'Mods & Injectors'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Products</title>
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
        .product-card {
            background: rgba(10,14,28,0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(147,51,234,0.12);
            border-radius: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .product-card:hover {
            border-color: rgba(147,51,234,0.3);
            transform: translateY(-4px);
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
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            z-index: 10;
        }
        .status-active { background: rgba(16,185,129,0.9); color: white; }
        .status-maintenance { background: rgba(245,158,11,0.9); color: white; }
    </style>
</head>
<body>
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
                <a href="dashboard.php" class="text-gray-300 hover:text-white">Dashboard</a>
                <a href="logout.php" class="text-red-400 hover:text-red-300">Logout</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="glass-card p-6 mb-6">
            <h1 class="text-2xl font-bold text-white">🛒 Our Products</h1>
            <p class="text-gray-400">Browse our collection of premium game accounts and mods</p>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <button class="category-btn active px-4 py-2 rounded-full bg-purple-500/20 text-purple-400" onclick="filterProducts('all')">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-btn px-4 py-2 rounded-full border border-white/10 text-gray-400 hover:bg-purple-500/20" onclick="filterProducts('<?php echo $cat; ?>')"><?php echo $cat; ?></button>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="productsGrid">
            <?php foreach ($products as $product): ?>
                <?php
                $finalPrice = $product['price'] * (1 - $product['discount'] / 100);
                $statusClass = $product['status'] === 'maintenance' ? 'status-maintenance' : 'status-active';
                $statusText = $product['status'] === 'maintenance' ? 'Maintenance' : 'Active';
                ?>
                <div class="product-card p-4" onclick="buyProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $finalPrice; ?>)">
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    <img src="<?php echo $product['image_url']; ?>" class="w-full h-40 object-cover rounded-xl mb-3">
                    <h3 class="text-white font-semibold mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="flex justify-between items-center">
                        <span class="text-purple-400 font-bold">₱<?php echo number_format($finalPrice, 2); ?></span>
                        <?php if ($product['discount'] > 0): ?>
                            <span class="text-gray-400 text-sm line-through">₱<?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-500 text-xs mt-2"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function buyProduct(id, name, price) {
            if (confirm(`Buy ${name} for ₱${price.toFixed(2)}?`)) {
                window.location.href = `checkout.php?product_id=${id}`;
            }
        }
        
        function filterProducts(category) {
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('bg-purple-500/20', 'text-purple-400'));
            event.target.classList.add('bg-purple-500/20', 'text-purple-400');
            
            const products = document.querySelectorAll('.product-card');
            if (category === 'all') {
                products.forEach(p => p.style.display = 'block');
            } else {
                products.forEach(p => {
                    const title = p.querySelector('h3').textContent;
                    if (title.includes(category) || (category === 'Mobile Legends' && title.includes('MLBB'))) {
                        p.style.display = 'block';
                    } else {
                        p.style.display = 'none';
                    }
                });
            }
        }
    </script>
</body>
</html>