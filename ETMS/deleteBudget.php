<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$budgetID = $data['budgetID'] ?? null;

if (!$budgetID) {
    echo json_encode(['status' => 'failure', 'message' => 'No budget ID provided']);
    exit();
}

// Soft delete by setting deleted_at to current datetime
$sql = "UPDATE BUDGET SET deleted_at = NOW() WHERE budgetID = ? AND userID = ? AND groupID IS NULL AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $budgetID, $userID);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Budget soft deleted']);
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Budget not found or already deleted']);
    }
} else {
    echo json_encode(['status' => 'failure', 'message' => 'Failed to update budget']);
}

$stmt->close();
$conn->close();
?>
