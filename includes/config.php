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
define('GCASH_NUMBER', '09*******42');
define('GCASH_NAME', 'J** F*');
define('BINANCE_WALLET', '**********************************');
define('BINANCE_NETWORK', 'BEP20');

// Telegram Bot
define('BOT_TOKEN', '8275629731:AAGnXqwqXha12L0QAGcVDGB9P0T1lxwMo9s');
define('ADMIN_TELEGRAM_ID', '8477982865');

// Start session
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
