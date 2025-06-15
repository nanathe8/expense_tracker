<?php
session_start();
include 'db.php'; // your database connection

header('Content-Type: application/json');

// Step 0: Check user session
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents('php://input'), true);

// Step 1: Validate budgetID
if (!isset($data['budgetID'])) {
    echo json_encode(['status' => 'error', 'message' => 'budgetID is required']);
    exit;
}

$budgetID = intval($data['budgetID']);

// Step 2: Get groupID and deleted_at
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
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Budget not found']);
    exit();
}
$stmt->close();

// Step 3: Update to recover budget (only if previously deleted)
$stmt = $conn->prepare("UPDATE BUDGET SET deleted_at = NULL WHERE budgetID = ? AND groupID = ? AND deleted_at IS NOT NULL");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("ii", $budgetID, $groupID);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Budget recovered successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Budget not found or already active']);
}
$stmt->close();
$conn->close();
?>
