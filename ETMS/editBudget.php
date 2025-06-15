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
    echo json_encode(['status' => 'failure', 'message' => 'Missing budget ID']);
    exit();
}

// Prepare dynamic query
$fields = [];
$params = [];
$types = '';

// Check which fields are provided
if (isset($data['budgetName'])) {
    $fields[] = "budgetName = ?";
    $params[] = $data['budgetName'];
    $types .= 's';
}

if (isset($data['budgetAmount'])) {
    $fields[] = "budgetAmount = ?";
    $params[] = $data['budgetAmount'];
    $types .= 'i';
}

if (isset($data['startDate'])) {
    $fields[] = "startDate = ?";
    $params[] = $data['startDate'];
    $types .= 's';
}

if (isset($data['endDate'])) {
    $fields[] = "endDate = ?";
    $params[] = $data['endDate'];
    $types .= 's';
}

// If no fields to update, stop
if (empty($fields)) {
    echo json_encode(['status' => 'failure', 'message' => 'No data to update']);
    exit();
}

// Add WHERE condition
$sql = "UPDATE budget SET " . implode(", ", $fields) . " WHERE budgetID = ? AND userID = ? AND groupID IS NULL";
$params[] = $budgetID;
$params[] = $userID;
$types .= 'ii';

// Prepare and bind
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update budget']);
}

$stmt->close();
$conn->close();
?>
