<?php
include 'db.php'; // Your DB connection file
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$groupName = $data['groupName'] ?? '';
$groupDescription = $data['groupDescription'] ?? '';

if (!$groupName) {
    echo json_encode(['status' => 'failure', 'message' => 'Group name is required']);
    exit();
}

// Generate invite token
$inviteToken = bin2hex(random_bytes(8));

try {
    $stmt = $pdo->prepare("INSERT INTO EXPENSE_GROUP (groupName, groupDescription, inviteToken) VALUES (?, ?, ?)");
    $stmt->execute([$groupName, $groupDescription, $inviteToken]);
    $groupID = $pdo->lastInsertId();

    // Add creator as group member with role 'admin'
    $stmt2 = $pdo->prepare("INSERT INTO USER_GROUP (userID, groupID, role) VALUES (?, ?, 'admin')");
    $stmt2->execute([$userID, $groupID]);

    echo json_encode([
        'status' => 'success',
        'groupID' => $groupID,
        'inviteToken' => $inviteToken,
        'message' => 'Group created successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'failure', 'message' => $e->getMessage()]);
}
