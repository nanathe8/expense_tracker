<?php
include 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$budgetID = $data['budgetID'] ?? null;

if (!$budgetID) {
    echo json_encode(['success' => false, 'message' => 'No budget ID provided']);
    exit();
}

// Debug log input values
error_log("Input budgetID: $budgetID, userID: $userID");

// Step 1: Get groupID for the budget
$sql = "SELECT groupID, deleted_at FROM BUDGET WHERE budgetID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $budgetID);
$stmt->execute();
$stmt->bind_result($groupID, $deletedAt);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Budget not found']);
    exit();
}
$stmt->close();

// Log budget deleted_at status
error_log("Budget deleted_at: " . ($deletedAt ?? 'NULL'));

if ($deletedAt !== null) {
    echo json_encode(['success' => false, 'message' => 'Budget already deleted']);
    exit();
}

// Step 2: Check if user is admin of that group
$sql = "SELECT 1 FROM USER_GROUP WHERE groupID = ? AND userID = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ii", $groupID, $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only admin can delete the budget.']);
    exit();
}
$stmt->close();

// Step 3: Soft delete the budget (set deleted_at to current datetime)
$sql = "UPDATE BUDGET SET deleted_at = NOW() WHERE budgetID = ? AND groupID = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ii", $budgetID, $groupID);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Budget soft deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Budget not found or already deleted']);
    }
} else {
    error_log("Attempting to delete budgetID: $budgetID for userID: $userID");
    echo json_encode(['success' => false, 'message' => 'Failed to update budget']);
}

$stmt->close();
$conn->close();
?>
