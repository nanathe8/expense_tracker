<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
        header('Location: login.php');
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];

// Get invite token from either GET or POST
$inviteToken = '';

// If it's a GET request (from browser link)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $inviteToken = $_GET['token'];
}
// If it's a POST request (from Flutter app)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $inviteToken = $data['inviteToken'] ?? '';
}

// If no token provided
if (!$inviteToken) {
    echo json_encode(['status' => 'failure', 'message' => 'Invite token required']);
    exit();
}

try {
    // Find group by token
    $stmt = $conn->prepare("SELECT groupID FROM EXPENSE_GROUP WHERE inviteToken = ?");
    $stmt->bind_param("s", $inviteToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $group = $result->fetch_assoc();

    if (!$group) {
        echo json_encode(['status' => 'failure', 'message' => 'Invalid invite token']);
        exit();
    }

    $groupID = $group['groupID'];

    // Check if user is already in the group
    $stmt2 = $conn->prepare("SELECT * FROM USER_GROUP WHERE userID = ? AND groupID = ?");
    $stmt2->bind_param("ii", $userID, $groupID);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->fetch_assoc()) {
        echo json_encode(['status' => 'failure', 'message' => 'You are already a member of this group']);
        exit();
    }

    // Add user to group
    $stmt3 = $conn->prepare("INSERT INTO USER_GROUP (userID, groupID, role) VALUES (?, ?, 'member')");
    $stmt3->bind_param("ii", $userID, $groupID);
    $stmt3->execute();

    echo json_encode(['status' => 'success', 'message' => 'Joined group successfully', 'groupID' => $groupID]);
} catch (Exception $e) {
    echo json_encode(['status' => 'failure', 'message' => $e->getMessage()]);
}
?>
