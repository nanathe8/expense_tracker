<?php
session_start();
header('Content-Type: application/json');
include 'db.php'; // Your database connection file

// Error handling to display errors during debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];

// SQL query to get all expenses linked to user's budgets or user's groups' budgets
$sql = "
    SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage, 
           b.budgetName, g.groupName, c.categoryID, u.name AS userName
    FROM EXPENSE e
    JOIN BUDGET b ON e.budgetID = b.budgetID
    LEFT JOIN CATEGORY c ON e.categoryID = c.categoryID
    LEFT JOIN EXPENSE_GROUP g ON b.groupID = g.groupID
    LEFT JOIN USERS u ON e.paidBy = u.userID  -- Assuming the 'paidBy' field links to 'userID' in the 'USERS' table
    WHERE 
        (b.userID = ? OR b.groupID IN (
            SELECT groupID FROM USER_GROUP WHERE userID = ?
        ))
        AND b.groupID IS NOT NULL
    ORDER BY e.date DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userID, $userID);
$stmt->execute();
$result = $stmt->get_result();

// Store expenses
$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = [
        'expenseID' => $row['expenseID'],
        'amount' => $row['amount'],
        'date' => $row['date'],
        'category' => $row['categoryName'],
        'categoryID' => $row['categoryID'], // Adding categoryID for the selected category in the dropdown
        'description' => $row['description'],
        'receiptImage' => $row['receiptImage'] ? "http://localhost/PSM1/ETMS/uploads/" . $row['receiptImage'] : null,
        'budgetName' => $row['budgetName'],
        'groupName' => $row['groupName'],
        'userName' => $row['userName'] ?? 'Unknown'
    ];
}

// Now we fetch all categories to populate the dropdown
$sqlCategories = "SELECT categoryID, categoryName FROM CATEGORY ORDER BY categoryName ASC";
$stmtCategories = $conn->prepare($sqlCategories);
$stmtCategories->execute();
$resultCategories = $stmtCategories->get_result();

$categories = [];
while ($row = $resultCategories->fetch_assoc()) {
    $categories[] = [
        'categoryID' => $row['categoryID'],
        'categoryName' => $row['categoryName']
    ];
}

// Return both expenses and categories
echo json_encode([
    'status' => 'success', 
    'expenses' => $expenses,
    'categories' => $categories
]);

// Close statements and connection
$stmt->close();
$stmtCategories->close();
$conn->close();
?>
