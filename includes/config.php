<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cluddy_shop');

// Site configuration
define('SITE_NAME', 'Cluddy Shop');
define('SITE_URL', 'http://localhost/cluddy-shop-php/');
define('ADMIN_EMAIL', 'admin@cluddy.com');

// Payment settings
define('GCASH_NUMBER', '09167314020');
define('GCASH_NAME', 'M** J** E**');
define('BINANCE_WALLET', '0x742d35Cc6634C0532925a3b844Bc9e7595f0b2a6');
define('BINANCE_NETWORK', 'BEP20');

// Telegram Bot
define('BOT_TOKEN', '8499837362:AAEFJQiF0wtkwtY7ZJHirPpdxEJ2z2tKvYo');
define('ADMIN_TELEGRAM_ID', '5318214551');

// Start session
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>