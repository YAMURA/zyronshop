<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'login') {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (loginUser($username, $password)) {
            $response = [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'fullname' => $_SESSION['fullname'],
                    'role' => $_SESSION['role'],
                    'balance' => $_SESSION['balance']
                ]
            ];
        } else {
            $response = ['success' => false, 'message' => 'Invalid username or password'];
        }
    }
    
    elseif ($action === 'register') {
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $fullname = $input['fullname'] ?? '';
        
        if (registerUser($username, $email, $password, $fullname)) {
            $response = ['success' => true, 'message' => 'Registration successful'];
        } else {
            $response = ['success' => false, 'message' => 'Username or email already exists'];
        }
    }
    
    elseif ($action === 'logout') {
        session_destroy();
        $response = ['success' => true, 'message' => 'Logged out'];
    }
}

echo json_encode($response);
?>