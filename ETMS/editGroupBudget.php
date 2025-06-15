<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$budgetID = $data['budgetID'] ?? null;
$budgetName = $data['budgetName'] ?? '';
$startDate = $data['startDate'] ?? '';
$endDate = $data['endDate'] ?? '';
$budgetAmount = isset($data['budgetAmount']) ? (float)$data['budgetAmount'] : 0;

if (!$budgetID || empty($budgetName) || $budgetAmount <= 0 || empty($startDate) || empty($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'Missing or invalid data']);
    exit();
}

// Step 1: Get groupID from budget
$sql = "SELECT groupID FROM BUDGET WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $budgetID);
$stmt->execute();
$stmt->bind_result($groupID);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'failure', 'message' => 'Budget not found']);
    exit();
}
$stmt->close();

// Step 2: Check if user is admin of that group
$sql = "SELECT * FROM USER_GROUP WHERE groupID = ? AND userID = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ii", $groupID, $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'failure', 'message' => 'Access denied. Only admin can edit the budget.']);
    exit();
}
$stmt->close();

// Step 3: Calculate totalExpenses for this budget
$sql = "SELECT SUM(amount) FROM EXPENSE WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $budgetID);
$stmt->execute();
$stmt->bind_result($totalExpenses);
$stmt->fetch();
$totalExpenses = $totalExpenses ?? 0; // handle null case
$stmt->close();

// Step 4: Calculate balance
$balance = $budgetAmount - $totalExpenses;
$balance = round($balance, 2);

// Step 5: Update the BUDGET table
$sql = "UPDATE BUDGET 
        SET budgetName = ?, 
            budgetAmount = ?, 
            totalExpenses = ?, 
            balance = ?, 
            startDate = ?, 
            endDate = ? 
        WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("sdddssi", $budgetName, $budgetAmount, $totalExpenses, $balance, $startDate, $endDate, $budgetID);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating budget', 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
