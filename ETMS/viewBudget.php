<?php
error_reporting(0);
ini_set('display_errors', 0);

include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];

$showDeleted = isset($_GET['showDeleted']) && $_GET['showDeleted'] === 'true';

if ($showDeleted) {
    $query = "SELECT * FROM budget WHERE userID = ? AND groupID IS NULL AND deleted_at IS NOT NULL";
} else {
    $query = "SELECT * FROM budget WHERE userID = ? AND groupID IS NULL AND deleted_at IS NULL";
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'SQL prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$budgets = [];
while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;
}

echo json_encode(['status' => 'success', 'budgets' => $budgets]);

$stmt->close();
$conn->close();
