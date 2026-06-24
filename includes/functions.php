<?php
require_once 'db.php';

// User functions
function registerUser($username, $email, $password, $fullname) {
    $db = Database::getInstance();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, fullname, balance, role, created_at) 
            VALUES (?, ?, ?, ?, 0, 'user', NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $fullname);
    
    if ($stmt->execute()) {
        return $db->lastInsertId();
    }
    return false;
}

function loginUser($username, $password) {
    $db = Database::getInstance();
    
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['balance'] = $row['balance'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Product functions
function getProducts($category = null, $limit = null) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM products WHERE status = 'active'";
    
    if ($category) {
        $sql .= " AND category = '$category'";
    }
    
    $sql .= " ORDER BY id DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $result = $db->getConnection()->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductById($id) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function addProduct($name, $category, $price, $discount, $description, $image, $stock, $status) {
    $db = Database::getInstance();
    $sql = "INSERT INTO products (name, category, price, discount, description, image_url, stock, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssddssis", $name, $category, $price, $discount, $description, $image, $stock, $status);
    return $stmt->execute();
}

// Order functions
function createOrder($userId, $username, $telegramUser, $productName, $amount, $paymentMethod, $receipt) {
    $db = Database::getInstance();
    $orderId = 'ORD' . time() . rand(100, 999);
    $status = 'pending';
    
    $sql = "INSERT INTO orders (order_id, user_id, username, telegram_username, product_name, amount, payment_method, receipt, status, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sisssdsss", $orderId, $userId, $username, $telegramUser, $productName, $amount, $paymentMethod, $receipt, $status);
    
    if ($stmt->execute()) {
        // Send Telegram notification
        sendTelegramNotification("🛒 NEW ORDER!\nOrder: $orderId\nUser: $username\nProduct: $productName\nAmount: ₱$amount\nMethod: $paymentMethod");
        return $orderId;
    }
    return false;
}

function getOrders($userId = null, $status = null) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM orders";
    $conditions = [];
    
    if ($userId) {
        $conditions[] = "user_id = $userId";
    }
    if ($status) {
        $conditions[] = "status = '$status'";
    }
    
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY id DESC";
    
    $result = $db->getConnection()->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateOrderStatus($orderId, $status) {
    $db = Database::getInstance();
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $status, $orderId);
    
    if ($stmt->execute()) {
        sendTelegramNotification("✅ ORDER $status\nOrder: $orderId");
        return true;
    }
    return false;
}

// Deposit functions
function createDeposit($userId, $username, $amount, $method, $receipt) {
    $db = Database::getInstance();
    $depositId = 'DEP' . time() . rand(100, 999);
    $status = 'pending';
    
    $sql = "INSERT INTO deposits (deposit_id, user_id, username, amount, method, receipt, status, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sisdsss", $depositId, $userId, $username, $amount, $method, $receipt, $status);
    
    if ($stmt->execute()) {
        sendTelegramNotification("💰 NEW DEPOSIT!\nDeposit: $depositId\nUser: $username\nAmount: ₱$amount\nMethod: $method");
        return $depositId;
    }
    return false;
}

function approveDeposit($depositId) {
    $db = Database::getInstance();
    
    // Get deposit details
    $sql = "SELECT * FROM deposits WHERE deposit_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $depositId);
    $stmt->execute();
    $deposit = $stmt->get_result()->fetch_assoc();
    
    if ($deposit) {
        // Update deposit status
        $sql = "UPDATE deposits SET status = 'approved' WHERE deposit_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $depositId);
        $stmt->execute();
        
        // Update user balance
        $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("di", $deposit['amount'], $deposit['user_id']);
        $stmt->execute();
        
        sendTelegramNotification("✅ DEPOSIT APPROVED!\nDeposit: $depositId\nUser: {$deposit['username']}\nAmount: ₱{$deposit['amount']}");
        return true;
    }
    return false;
}

// Feedback function
function addFeedback($userId, $username, $message, $rating, $image = null) {
    $db = Database::getInstance();
    $sql = "INSERT INTO feedback (user_id, username, message, rating, image, timestamp) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("issis", $userId, $username, $message, $rating, $image);
    
    if ($stmt->execute()) {
        $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        sendTelegramNotification("⭐ NEW FEEDBACK!\nUser: $username\nRating: $stars\nMessage: $message");
        return true;
    }
    return false;
}

// Support chat function
function addSupportMessage($userId, $username, $message, $image = null) {
    $db = Database::getInstance();
    $sql = "INSERT INTO support_chats (user_id, username, message, image, timestamp, replied) 
            VALUES (?, ?, ?, ?, NOW(), 0)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("isss", $userId, $username, $message, $image);
    
    if ($stmt->execute()) {
        sendTelegramNotification("💬 NEW SUPPORT MESSAGE!\nUser: $username\nMessage: $message");
        return true;
    }
    return false;
}

// Creator application
function submitCreatorApplication($userId, $username, $name, $telegram, $platform, $followers, $reason) {
    $db = Database::getInstance();
    $sql = "INSERT INTO creator_applications (user_id, username, name, telegram, platform, followers, reason, status, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("issssis", $userId, $username, $name, $telegram, $platform, $followers, $reason);
    
    if ($stmt->execute()) {
        sendTelegramNotification("🎬 NEW CREATOR APPLICATION!\nName: $name\nTelegram: $telegram\nPlatform: $platform\nFollowers: $followers");
        return true;
    }
    return false;
}

// Reseller application
function submitResellerApplication($userId, $username, $name, $telegram, $email, $businessName, $tier, $storeLink, $reason) {
    $db = Database::getInstance();
    $sql = "INSERT INTO reseller_applications (user_id, username, name, telegram, email, business_name, tier, store_link, reason, status, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("issssssss", $userId, $username, $name, $telegram, $email, $businessName, $tier, $storeLink, $reason);
    
    if ($stmt->execute()) {
        sendTelegramNotification("🤝 NEW RESELLER APPLICATION!\nName: $name\nTelegram: $telegram\nBusiness: $businessName\nTier: $tier");
        return true;
    }
    return false;
}

// Announcement function
function addAnnouncement($message, $type = 'info') {
    $db = Database::getInstance();
    $sql = "INSERT INTO announcements (message, type, timestamp, is_active) VALUES (?, ?, NOW(), 1)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $message, $type);
    return $stmt->execute();
}

function getAnnouncements() {
    $db = Database::getInstance();
    $sql = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 10";
    $result = $db->getConnection()->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Telegram notification
function sendTelegramNotification($message) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => ADMIN_TELEGRAM_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// Upload file
function uploadFile($file, $folder = 'uploads/') {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/cluddy-shop-php/assets/' . $folder;
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'assets/' . $folder . $file_name;
    }
    return false;
}
?>