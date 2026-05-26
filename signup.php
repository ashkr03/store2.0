<?php
// signup.php - User Registration

// CORS headers - React ko access dene ke liye
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Preflight request handle karo
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$servername = "beceibtanv2wzwlflngj-mysql.services.clever-cloud.com";
$username = "uiwfgilsjidr1pcw";
$dbname = "beceibtanv2wzwlflngj";
$password = "LZpa1xl2FvzGOJweli4Z";
$port = 3306;

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Connection check
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// POST request handle 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // JSON data receive 
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
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
    
    // Check if email already exists
    $check_query = "SELECT id FROM store_auth WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "error" => "Email already registered"
        ]);
        exit();
    }
    
    // Password hash  (security ke liye)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user data
    $insert_query = "INSERT INTO store_auth (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Account created successfully",
            "name" => $name
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Registration failed: " . $stmt->error
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
