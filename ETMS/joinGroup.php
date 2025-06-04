<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);
$inviteToken = $data['inviteToken'] ?? '';

if (!$inviteToken) {
    echo json_encode(['status' => 'failure', 'message' => 'Invite token required']);
    exit();
}

try {
    // Find group by inviteToken
    $stmt = $pdo->prepare("SELECT groupID FROM EXPENSE_GROUP WHERE inviteToken = ?");
    $stmt->execute([$inviteToken]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        echo json_encode(['status' => 'failure', 'message' => 'Invalid invite token']);
        exit();
    }

    $groupID = $group['groupID'];

    // Check if user already in group
    $stmt2 = $pdo->prepare("SELECT * FROM USER_GROUP WHERE userID = ? AND groupID = ?");
    $stmt2->execute([$userID, $groupID]);

    if ($stmt2->fetch()) {
        echo json_encode(['status' => 'failure', 'message' => 'You are already a member of this group']);
        exit();
    }

    // Add user to group with role 'member'
    $stmt3 = $pdo->prepare("INSERT INTO USER_GROUP (userID, groupID, role) VALUES (?, ?, 'member')");
    $stmt3->execute([$userID, $groupID]);

    echo json_encode(['status' => 'success', 'message' => 'Joined group successfully', 'groupID' => $groupID]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'failure', 'message' => $e->getMessage()]);
}
