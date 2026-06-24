<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category = $_GET['category'] ?? null;
    $productId = $_GET['id'] ?? null;
    
    if ($productId) {
        $product = getProductById($productId);
        if ($product) {
            $response = ['success' => true, 'product' => $product];
        } else {
            $response = ['success' => false, 'message' => 'Product not found'];
        }
    } else {
        $products = getProducts($category);
        $response = ['success' => true, 'products' => $products];
    }
}

echo json_encode($response);
?>