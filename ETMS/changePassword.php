<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['currentPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['error' => 'Please fill in all fields.']);
    exit;
}

$userID = $_SESSION['userID'];

// Check current password
$stmt = $conn->prepare("SELECT password FROM USERS WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row || !password_verify($currentPassword, $row['password'])) {
    echo json_encode(['error' => 'Current password is incorrect.']);
    exit;
}

// Update password
$newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE USERS SET password = ? WHERE userID = ?");
$stmt->bind_param("si", $newHashed, $userID);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update password.']);
}
?>
