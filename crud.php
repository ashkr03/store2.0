<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$servername = "beceibtanv2wzwlflngj-mysql.services.clever-cloud.com";
$username = "uiwfgilsjidr1pcw";
$dbname = "beceibtanv2wzwlflngj";
$password = "LZpa1xl2FvzGOJweli4Z";
$port = 3306;


$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "error" => "DB connection failed: " . $conn->connect_error
    ]);
    exit();
}
  //Get handler~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id > 0) {

        // Single item fetch for Edit

        $stmt = $conn->prepare("SELECT * FROM itemlist WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        echo json_encode($item);
    } else {

        // Sari list fetch for ItemList

        $result = $conn->query("SELECT * FROM itemlist ORDER BY id DESC");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode($items);
    }
    exit();
}
   //Post hander~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~`

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Issue Item request hai ya Add Item?
    if (isset($input['itemId'])) {
        // === ISSUE ITEM ===
        $item_id   = intval($input['itemId'] ?? 0);
        $qty_issue = intval($input['quantity'] ?? 0);
        $dept      = trim($input['department'] ?? '');
        $itemName  = trim($input['itemName'] ?? '');
        $itemModel = trim($input['itemModel'] ?? '');
        $issued_by = trim($input['issuedBy'] ?? '');

        if (!$item_id || !$qty_issue || !$dept || !$issued_by) {
            echo json_encode(["success" => false, "error" => "All fields required"]);
            exit();
        }

        // Current quantity check karo
        $stmt = $conn->prepare("SELECT Qty FROM itemlist WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();

        if (!$item) {
            echo json_encode(["success" => false, "error" => "Item not found"]);
            exit();
        }

        $currentQty = intval($item['Qty']);

        if ($qty_issue > $currentQty) {
            echo json_encode(["success" => false, "error" => "Only $currentQty units available"]);
            exit();
        }

        // Reduce Quantity from itemlist
        $newQty = $currentQty - $qty_issue;
        $stmt = $conn->prepare("UPDATE itemlist SET Qty = ? WHERE id = ?");
        $stmt->bind_param("ii", $newQty, $item_id);
        $stmt->execute();

        // Enter record in issued_items
        $stmt = $conn->prepare("INSERT INTO issued_items (item_id, item_name, item_model, quantity, department, issued_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $item_id, $itemName, $itemModel, $qty_issue, $dept, $issued_by);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Item issued successfully",
                "updatedQuantity" => $newQty
            ]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }

    } else {
        // === ADD ITEM ===
        $Item  = trim($input['Item']  ?? '');
        $Model = trim($input['Model'] ?? '');
        $Qty   = trim($input['Qty']   ?? '');

        if (empty($Item) || empty($Model) || empty($Qty)) {
            echo json_encode(["success" => false, "error" => "All fields are required"]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO itemlist (Item, Model, Qty, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $Item, $Model, $Qty);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Item added successfully"]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }

        $stmt->close();
    }
}

    //PUT handler~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = intval($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);

    $Item  = trim($input['Item']  ?? '');
    $Model = trim($input['Model'] ?? '');
    $Qty   = trim($input['Qty']   ?? '');

    if ($id <= 0 || empty($Item) || empty($Model) || empty($Qty)) {
        echo json_encode(["success" => false, "error" => "All fields are required"]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE itemlist SET Item=?, Model=?, Qty=? WHERE id=?");
    $stmt->bind_param("sssi", $Item, $Model, $Qty, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item updated successfully"]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
}

    //Delete handler~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(["success" => false, "error" => "Invalid ID"]);
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM itemlist WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item deleted"]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>
