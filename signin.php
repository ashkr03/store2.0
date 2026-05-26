<?php
// signin.php - User Login

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
$servername = "beceibtanv2wzwlflngj-mysql.services.clever-cloud.com";
$username = "uiwfgilsjidr1pcw";
$dbname = "beceibtanv2wzwlflngj";
$password = "LZpa1xl2FvzGOJweli4Z";
$port = 3306;

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

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
    
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    
    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode([
            "success" => false,
            "error" => "Email and password are required"
        ]);
        exit();
    }
    
    // Check user credentials
    $query = "SELECT id, name, email, password FROM store_auth WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid email or password"
        ]);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Password verify karo
    if (password_verify($password, $user['password'])) {
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "name" => $user['name'],
            "email" => $user['email']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Invalid email or password"
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
