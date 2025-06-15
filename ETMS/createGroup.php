<?php
include 'db.php'; // your DB connection, make sure $conn is defined
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents("php://input"), true);

$groupName = $data['groupName'] ?? '';
$groupDescription = $data['groupDiscussion'] ?? ''; // note: match key from Dart!

if (!$groupName) {
    echo json_encode(['status' => 'failure', 'message' => 'Group name is required']);
    exit();
}

// Generate invite token & timestamps on server side
$inviteToken = bin2hex(random_bytes(4)); // 8 chars token
$inviteCreatedAt = date('Y-m-d H:i:s');
$inviteExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert into EXPENSE_GROUP
$stmt = $conn->prepare("INSERT INTO EXPENSE_GROUP (groupName, groupDescription, inviteToken, inviteCreatedAt, inviteExpiresAt) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'failure', 'message' => $conn->error]);
    exit();
}
$stmt->bind_param("sssss", $groupName, $groupDescription, $inviteToken, $inviteCreatedAt, $inviteExpiresAt);
if (!$stmt->execute()) {
    echo json_encode(['status' => 'failure', 'message' => $stmt->error]);
    exit();
}

$groupID = $stmt->insert_id;

// Insert user as admin in USER_GROUP
$stmt2 = $conn->prepare("INSERT INTO USER_GROUP (userID, groupID, role) VALUES (?, ?, 'admin')");
if (!$stmt2) {
    echo json_encode(['status' => 'failure', 'message' => $conn->error]);
    exit();
}
$stmt2->bind_param("ii", $userID, $groupID);
if (!$stmt2->execute()) {
    echo json_encode(['status' => 'failure', 'message' => $stmt2->error]);
    exit();
}

// Success response with invite token and group ID
echo json_encode([
    'status' => 'success',
    'groupID' => $groupID,
    'inviteToken' => $inviteToken,
    'message' => 'Group created successfully'
]);
exit();
?>
