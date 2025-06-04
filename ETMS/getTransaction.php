<?php
session_start();
include 'db.php'; // your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];

header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['fetchAll']) && $_GET['fetchAll'] == 'true') {
            // Fetch all transactions with category and budget names
            $sql = "
                SELECT 
                    e.*, 
                    c.name AS categoryName, 
                    b.name AS budgetName 
                FROM 
                    EXPENSE e
                JOIN 
                    CATEGORY c ON e.categoryID = c.categoryID
                JOIN 
                    BUDGET b ON e.budgetID = b.budgetID
                WHERE 
                    b.userID = ?
                ORDER BY 
                    e.date DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

            echo json_encode(["success" => true, "data" => $transactions]);
            exit();

        } elseif (isset($_GET['id'])) {
            // Fetch single transaction by expenseID with ownership check
            $id = intval($_GET['id']);

            $sql = "
                SELECT e.*, c.name AS categoryName, b.name AS budgetName
                FROM EXPENSE e
                JOIN CATEGORY c ON e.categoryID = c.categoryID
                JOIN BUDGET b ON e.budgetID = b.budgetID
                WHERE e.expenseID = ? AND b.userID = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode(["success" => true, "data" => $row]);
            } else {
                echo json_encode(["success" => false, "message" => "Expense not found or access denied"]);
            }
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "No valid parameter provided"]);
            exit();
        }
        break;

    case 'POST':
        // Decode the input
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['action'])) {
            echo json_encode(["success" => false, "message" => "Invalid input"]);
            exit();
        }

        $action = $data['action'];

        if ($action === 'update') {
            // Update a transaction
            $id = intval($data['expenseID']);  // Fixed here: use expenseID to identify the expense
            $description = $data['description'] ?? '';
            $amount = floatval($data['amount']);
            $categoryID = intval($data['categoryID'] ?? 0);
            $date = $data['date'] ?? null;
            $receiptImage = $data['receiptImage'] ?? null;

            // Validate date format
            if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                echo json_encode(["success" => false, "message" => "Invalid date format"]);
                exit();
            }

            // Validate amount (optional, e.g., amount > 0)
            if ($amount <= 0) {
                echo json_encode(["success" => false, "message" => "Amount must be greater than zero"]);
                exit();
            }

            // Validate categoryID (optional)
            if ($categoryID <= 0) {
                echo json_encode(["success" => false, "message" => "Invalid category ID"]);
                exit();
            }

            // Check ownership
            $sqlCheck = "
                SELECT e.expenseID 
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                WHERE e.expenseID = ? AND b.userID = ?
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("ii", $id, $user_id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($resultCheck->num_rows === 0) {
                echo json_encode(["success" => false, "message" => "Expense not found or access denied"]);
                exit();
            }

            // Update expense
            $sqlUpdate = "
                UPDATE EXPENSE 
                SET description = ?, amount = ?, categoryID = ?, date = ?, receiptImage = ?
                WHERE expenseID = ?
            ";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("sdissi", $description, $amount, $categoryID, $date, $receiptImage, $id);

            if ($stmtUpdate->execute()) {
                echo json_encode(["success" => true, "message" => "Expense updated"]);
            } else {
                echo json_encode(["success" => false, "message" => "Update failed"]);
            }

        } elseif ($action === 'delete') {
            // Delete a transaction
            $id = intval($data['id']);

            // Check ownership
            $sqlCheck = "
                SELECT e.expenseID 
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                WHERE e.expenseID = ? AND b.userID = ?
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("ii", $id, $user_id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($resultCheck->num_rows === 0) {
                echo json_encode(["success" => false, "message" => "Expense not found or access denied"]);
                exit();
            }

            // Delete
            $sqlDelete = "DELETE FROM EXPENSE WHERE expenseID = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param("i", $id);

            if ($stmtDelete->execute()) {
                echo json_encode(["success" => true, "message" => "Expense deleted"]);
            } else {
                echo json_encode(["success" => false, "message" => "Delete failed"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Unknown action"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Unsupported method"]);
        break;
}
