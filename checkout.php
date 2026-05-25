<?php
// checkout.php - Save billing and order details to database

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = "localhost";
$username = "webuser";
$password = "password123";
$database = "user_auth";

// Database connection
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed"
    ]);
    exit();
}

// POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Extract data
    $order_id = $input['order_id'] ?? 0;
    $user_email = $input['user_email'] ?? '';
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $pincode = trim($input['pincode'] ?? '');
    $payment_method = $input['payment_method'] ?? 'cod';
    $subtotal = floatval($input['subtotal'] ?? 0);
    $tax = floatval($input['tax'] ?? 0);
    $total_amount = floatval($input['total_amount'] ?? 0);
    $order_items = json_encode($input['order_items'] ?? []);
    
    // Validation
    if (empty($order_id) || empty($full_name) || empty($email) || 
        empty($phone) || empty($address) || empty($city) || empty($pincode)) {
        echo json_encode([
            "success" => false,
            "error" => "All fields are required"
        ]);
        exit();
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid email format"
        ]);
        exit();
    }
    
    // Phone validation (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid phone number. Must be 10 digits."
        ]);
        exit();
    }
    
    // Pincode validation (6 digits)
    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid pincode. Must be 6 digits."
        ]);
        exit();
    }
    
    // Check if order_id already exists
    $check_query = "SELECT id FROM billing_details WHERE order_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "error" => "Order ID already exists"
        ]);
        exit();
    }
    
    // Insert billing details
    $insert_query = "INSERT INTO billing_details 
        (order_id, user_email, full_name, email, phone, address, city, pincode, 
         payment_method, subtotal, tax, total_amount, order_items, order_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "issssssssddds",
        $order_id,
        $user_email,
        $full_name,
        $email,
        $phone,
        $address,
        $city,
        $pincode,
        $payment_method,
        $subtotal,
        $tax,
        $total_amount,
        $order_items
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Order placed successfully",
            "order_id" => $order_id,
            "total" => $total_amount
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Failed to save order: " . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method"
    ]);
}

$conn->close();
?>
