<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    if ($userId) {
        $orders = getOrders($userId);
        $response = ['success' => true, 'orders' => $orders];
    } else {
        $response = ['success' => false, 'message' => 'User ID required'];
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $response = ['success' => false, 'message' => 'Please login first'];
        echo json_encode($response);
        exit;
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $telegram_user = $_POST['telegram_user'] ?? '';
    $receipt = $_FILES['receipt'] ?? null;
    
    $product = getProductById($product_id);
    if (!$product) {
        $response = ['success' => false, 'message' => 'Product not found'];
        echo json_encode($response);
        exit;
    }
    
    if ($receipt && $receipt['error'] === UPLOAD_ERR_OK) {
        $receiptPath = uploadFile($receipt, 'receipts/');
        
        if ($receiptPath) {
            $orderId = createOrder(
                $user_id,
                $_SESSION['username'],
                $telegram_user,
                $product['name'],
                $amount,
                $payment_method,
                $receiptPath
            );
            
            if ($orderId) {
                $response = ['success' => true, 'message' => 'Order placed successfully', 'order_id' => $orderId];
            } else {
                $response = ['success' => false, 'message' => 'Failed to place order'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Failed to upload receipt'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Receipt is required'];
    }
}

echo json_encode($response);
?>