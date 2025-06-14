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
        if (isset($_GET['fetchAll']) && $_GET['fetchAll'] === 'true') {
            $sql = "
                SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage, b.budgetName
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                JOIN CATEGORY c ON e.categoryID = c.categoryID
                WHERE b.userID = ? AND b.groupID IS NULL AND b.deleted_at IS NULL
                ORDER BY e.date DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $expenses = [];
            while ($row = $result->fetch_assoc()) {
                $expenses[] = [
                    'expenseID' => $row['expenseID'],
                    'amount' => $row['amount'],
                    'date' => $row['date'],
                    'category' => $row['categoryName'],
                    'description' => $row['description'],
                    'receiptImage' => $row['receiptImage'] ? "http://localhost/PSM1/ETMS/uploads/" . $row['receiptImage'] : null,
                    'budgetName' => $row['budgetName'] ?? 'No Budget'
                ];
            }
            echo json_encode(["success" => true, "expenses" => $expenses]);
            $stmt->close();
            exit();

        } elseif (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "
                SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                JOIN CATEGORY c ON e.categoryID = c.categoryID
                WHERE e.expenseID = ? AND b.userID = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $expense = [
                    'expenseID' => $row['expenseID'],
                    'amount' => $row['amount'],
                    'date' => $row['date'],
                    'category' => $row['categoryName'],
                    'description' => $row['description'],
                    'receiptImage' => $row['receiptImage'] ? "http://localhost/PSM1/ETMS/uploads/" . $row['receiptImage'] : null
                ];
                echo json_encode(["success" => true, "expense" => $expense]);
            } else {
                echo json_encode(["success" => false, "message" => "Expense not found or access denied"]);
            }
            $stmt->close();
            exit();

        } else {
            echo json_encode(["success" => false, "message" => "No valid parameter provided"]);
            exit();
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['action'])) {
            echo json_encode(["success" => false, "message" => "Invalid input"]);
            exit();
        }

        $action = $data['action'];

        if ($action === 'update') {
            $id = intval($data['expenseID']);
            $description = $data['description'] ?? '';
            $amount = floatval($data['amount']);
            $categoryID = intval($data['categoryID'] ?? 0);
            $date = $data['date'] ?? null;
            $receiptImage = $data['receiptImage'] ?? null;

            if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                echo json_encode(["success" => false, "message" => "Invalid date format"]);
                exit();
            }
            if ($amount <= 0) {
                echo json_encode(["success" => false, "message" => "Amount must be greater than zero"]);
                exit();
            }
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
                $stmtCheck->close();
                exit();
            }
            $stmtCheck->close();

            // Update expense (allow NULL for receiptImage)
            $sqlUpdate = "
                UPDATE EXPENSE 
                SET description = ?, amount = ?, categoryID = ?, date = ?, receiptImage = ?
                WHERE expenseID = ?
            ";
            $stmtUpdate = $conn->prepare($sqlUpdate);

            // Bind receiptImage correctly (string or null)
            if ($receiptImage === null) {
                $stmtUpdate->bind_param("sdissi", $description, $amount, $categoryID, $date, $receiptImage, $id);
            } else {
                $stmtUpdate->bind_param("sdissi", $description, $amount, $categoryID, $date, $receiptImage, $id);
            }

            if ($stmtUpdate->execute()) {
                echo json_encode(["success" => true, "message" => "Expense updated"]);
            } else {
                echo json_encode(["success" => false, "message" => "Update failed"]);
            }
            $stmtUpdate->close();

        } elseif ($action === 'delete') {
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
                $stmtCheck->close();
                exit();
            }
            $stmtCheck->close();

            // Delete
            $sqlDelete = "DELETE FROM EXPENSE WHERE expenseID = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param("i", $id);

            if ($stmtDelete->execute()) {
                echo json_encode(["success" => true, "message" => "Expense deleted"]);
            } else {
                echo json_encode(["success" => false, "message" => "Delete failed"]);
            }
            $stmtDelete->close();

        } else {
            echo json_encode(["success" => false, "message" => "Unknown action"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Unsupported method"]);
        break;
}

$conn->close();
