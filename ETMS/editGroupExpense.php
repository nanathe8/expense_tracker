<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$expenseID = $data['expenseID'] ?? null;
$amount = isset($data['amount']) ? (float)$data['amount'] : 0;
$date = $data['date'] ?? null;
$categoryName = $data['category'] ?? null;  // category name sent from Flutter
$description = $data['description'] ?? '';
$receiptImage = isset($data['receiptImage']) && !empty($data['receiptImage']) ? $data['receiptImage'] : null;

// Check for required fields
if (!$expenseID) {
    echo json_encode(['status' => 'failure', 'message' => 'Expense ID is required']);
    exit();
}
if ($amount <= 0) {
    echo json_encode(['status' => 'failure', 'message' => 'Amount must be greater than zero']);
    exit();
}
if (!$date) {
    echo json_encode(['status' => 'failure', 'message' => 'Date is required']);
    exit();
}
if (!$categoryName || empty($categoryName)) {
    echo json_encode(['status' => 'failure', 'message' => 'Category is required']);
    exit();
}

// Step 1: Get budgetID, groupID, budgetAmount from EXPENSE and BUDGET tables
$sql = "SELECT E.budgetID, B.groupID, B.budgetAmount FROM EXPENSE E 
        JOIN BUDGET B ON E.budgetID = B.budgetID 
        WHERE E.expenseID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $expenseID);
$stmt->execute();
$stmt->bind_result($budgetID, $groupID, $budgetAmount);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'failure', 'message' => 'Expense not found']);
    exit();
}
$stmt->close();

// Step 2: Fetch the categoryID based on categoryName
$sql = "SELECT categoryID FROM CATEGORY WHERE categoryName = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $categoryName);  // category name is passed from Flutter
$stmt->execute();
$stmt->bind_result($categoryID);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'failure', 'message' => 'Invalid category']);
    exit();
}
$stmt->close();

// Step 3: Check if user is admin in the group
$sql = "SELECT 1 FROM USER_GROUP WHERE groupID = ? AND userID = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ii", $groupID, $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'failure', 'message' => 'Access denied. Only admin can edit this expense.']);
    exit();
}
$stmt->close();

// Step 4: Update the expense with the new values
$sql = "UPDATE EXPENSE SET amount = ?, date = ?, categoryID = ?, description = ?, receiptImage = ? WHERE expenseID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

// Bind the parameters (categoryID instead of category name)
if ($receiptImage === null) {
    $stmt->bind_param("dssssi", $amount, $date, $categoryID, $description, $receiptImage, $expenseID);
} else {
    $stmt->bind_param("dsssssi", $amount, $date, $categoryID, $description, $receiptImage, $expenseID);
}

if (!$stmt->execute()) {
    echo json_encode(['status' => 'failure', 'message' => 'Failed to update expense: ' . $stmt->error]);
    exit();
}

$stmt->close();

// Step 5: Recalculate totalExpenses for the budget
$sql = "SELECT IFNULL(SUM(amount),0) FROM EXPENSE WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $budgetID);
$stmt->execute();
$stmt->bind_result($totalExpenses);
$stmt->fetch();
$stmt->close();

$balance = round($budgetAmount - $totalExpenses, 2);

// Step 6: Update BUDGET totals
$sql = "UPDATE BUDGET SET totalExpenses = ?, balance = ? WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ddi", $totalExpenses, $balance, $budgetID);
if (!$stmt->execute()) {
    echo json_encode(['status' => 'failure', 'message' => 'Failed to update budget: ' . $stmt->error]);
    exit();
}
$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Expense updated successfully']);
$conn->close();
?>
