<?php
require_once 'includes/config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "✅ Database created successfully<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db(DB_NAME);

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100) NOT NULL,
        balance DECIMAL(10,2) DEFAULT 0,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at DATETIME NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        discount DECIMAL(5,2) DEFAULT 0,
        description TEXT,
        image_url VARCHAR(500),
        stock INT DEFAULT 1,
        status ENUM('active', 'maintenance') DEFAULT 'active',
        created_at DATETIME NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        telegram_username VARCHAR(100),
        product_name VARCHAR(200) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        receipt VARCHAR(500),
        notes TEXT,
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deposit_id VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        method VARCHAR(50) NOT NULL,
        receipt VARCHAR(500),
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        rating INT DEFAULT 0,
        image VARCHAR(500),
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS support_chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        image VARCHAR(500),
        reply TEXT,
        replied INT DEFAULT 0,
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS creator_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        telegram VARCHAR(100) NOT NULL,
        platform VARCHAR(200) NOT NULL,
        followers INT,
        reason TEXT,
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS reseller_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        telegram VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        business_name VARCHAR(200),
        tier VARCHAR(50),
        store_link VARCHAR(500),
        reason TEXT,
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        type ENUM('info', 'promo', 'urgent') DEFAULT 'info',
        timestamp DATETIME NOT NULL,
        is_active INT DEFAULT 1
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table created successfully<br>";
    } else {
        echo "❌ Error creating table: " . $conn->error . "<br>";
    }
}

// Insert default admin user
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, fullname, role, created_at) 
        VALUES ('admin', 'admin@cluddy.com', '$hashed_password', 'Administrator', 'admin', NOW())";
$conn->query($sql);

echo "<br>🎉 Installation complete!<br>";
echo "🔐 Admin Login: <strong>admin</strong> / <strong>admin123</strong><br>";
echo "<a href='index.html'>Go to Website</a>";

$conn->close();
?>