<?php
header('Content-Type: application/json');
session_start();
include 'db.php'; // Your database connection file

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Optional: Check if user is logged in if you want to restrict category access
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$sql = "SELECT categoryID, categoryName FROM CATEGORY ORDER BY categoryName ASC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'categories' => $categories]);
?>