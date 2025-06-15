<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['fetchAll']) && $_GET['fetchAll'] === 'true') {
        $sql = "
            SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage, b.budgetName, g.groupName
            FROM EXPENSE e
            JOIN BUDGET b ON e.budgetID = b.budgetID
            LEFT JOIN CATEGORY c ON e.categoryID = c.categoryID
            LEFT JOIN EXPENSE_GROUP g ON b.groupID = g.groupID
            WHERE 
                (b.userID = ? OR b.groupID IN (
                    SELECT groupID FROM USER_GROUP WHERE userID = ?
                ))
                AND b.groupID IS NOT NULL
            ORDER BY e.date DESC
        ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $userID);


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
                    'budgetName' => $row['budgetName'],
                    'groupName' => $row['groupName']
                ];
            }
            echo json_encode(["success" => true, "expenses" => $expenses]);
            $stmt->close();
            exit();
            
        } elseif (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "
                SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage,
                       eg.groupName
                FROM EXPENSE e
                LEFT JOIN CATEGORY c ON e.categoryID = c.categoryID
                LEFT JOIN BUDGET b ON e.budgetID = b.budgetID
                LEFT JOIN EXPENSE_GROUP eg ON b.groupID = eg.groupID
                LEFT JOIN USER_GROUP ug ON eg.groupID = ug.groupID
                WHERE e.expenseID = ? AND ug.userID = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode([
                    "success" => true,
                    "expense" => [
                        'expenseID' => $row['expenseID'],
                        'amount' => $row['amount'],
                        'date' => $row['date'],
                        'category' => $row['categoryName'],
                        'description' => $row['description'],
                        'receiptImage' => $row['receiptImage'] ? "http://localhost/PSM1/ETMS/uploads/" . $row['receiptImage'] : null,
                        'groupName' => $row['groupName']
                    ]
                ]);
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
            $categoryID = intval($data['categoryID']);
            $date = $data['date'] ?? null;
            $receiptImage = $data['receiptImage'] ?? null;

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                echo json_encode(["success" => false, "message" => "Invalid date format"]);
                exit();
            }
            if ($amount <= 0) {
                echo json_encode(["success" => false, "message" => "Amount must be greater than zero"]);
                exit();
            }

            $sqlCheck = "
                SELECT e.expenseID
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                JOIN USER_GROUP ug ON b.groupID = ug.groupID
                WHERE e.expenseID = ? AND ug.userID = ?
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmt->bind_param("ii", $id, $userID);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows === 0) {
                echo json_encode(["success" => false, "message" => "Not authorized or not found"]);
                $stmtCheck->close();
                exit();
            }
            $stmtCheck->close();

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
            $stmtUpdate->close();

        } elseif ($action === 'delete') {
            $id = intval($data['id']);

            $sqlCheck = "
                SELECT e.expenseID
                FROM EXPENSE e
                JOIN BUDGET b ON e.budgetID = b.budgetID
                JOIN USER_GROUP ug ON b.groupID = ug.groupID
                WHERE e.expenseID = ? AND ug.userID = ?
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmt->bind_param("ii", $id, $userID);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows === 0) {
                echo json_encode(["success" => false, "message" => "Not authorized or not found"]);
                $stmtCheck->close();
                exit();
            }
            $stmtCheck->close();

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
