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
$groupID = $data['groupID'] ?? 0;

if (!$groupID) {
    echo json_encode(['status' => 'failure', 'message' => 'Group ID required']);
    exit();
}

try {
    // Check if user belongs to group
    $stmtCheck = $pdo->prepare("SELECT * FROM USER_GROUP WHERE userID = ? AND groupID = ?");
    $stmtCheck->execute([$userID, $groupID]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['status' => 'failure', 'message' => 'Access denied']);
        exit();
    }

    // Get group info
    $stmtGroup = $pdo->prepare("SELECT groupName, groupDescription FROM EXPENSE_GROUP WHERE groupID = ?");
    $stmtGroup->execute([$groupID]);
    $group = $stmtGroup->fetch(PDO::FETCH_ASSOC);

    // Get members
    $stmtMembers = $pdo->prepare(
        "SELECT U.userID, U.name, UG.role FROM USER_GROUP UG JOIN USER U ON UG.userID = U.userID WHERE UG.groupID = ?"
    );
    $stmtMembers->execute([$groupID]);
    $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'group' => $group,
        'members' => $members,
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'failure', 'message' => $e->getMessage()]);
}
