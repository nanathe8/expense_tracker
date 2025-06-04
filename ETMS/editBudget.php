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
$budgetName = $data['budgetName'] ?? '';
$startDate = $data['startDate'] ?? '';
$endDate = $data['endDate'] ?? '';
$budgetAmount = $data['budgetAmount'] ?? '';

if (!$budgetID || empty($budgetName) || empty($budgetAmount) || empty($startDate) || empty($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'Missing data']);
    exit();
}

$sql = "UPDATE budget SET budgetName = ?, budgetAmount = ?, startDate = ?, endDate = ? WHERE budgetID = ? AND userID = ? AND groupID IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisii", $budgetName, $budgetAmount, $startDate, $endDate, $budgetID, $userID);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update budget']);
}

$stmt->close();
$conn->close();
?>
