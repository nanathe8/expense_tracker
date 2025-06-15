<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Check if session userID is set
if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'User  not logged in']);
    exit;
}

$userID = $_SESSION['userID'];
$response = [];

// Get user info
$userQuery = "SELECT name, email, avatar FROM USERS WHERE userID = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$userResult = $stmt->get_result();
$response['user'] = $userResult->fetch_assoc() ?: [];
if (!empty($response['user'])) {
    $response['user']['avatar'];
}

// Personal budget summary
$budgetQuery = "
    SELECT 
        COUNT(*) as totalBudgets,
        SUM(totalIncome) as totalIncome,
        SUM(totalExpenses) as totalExpenses,
        SUM(balance) as totalBalance
    FROM BUDGET
    WHERE userID = ? AND groupID IS NULL AND deleted_at IS NULL
";
$stmt = $conn->prepare($budgetQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$budgetResult = $stmt->get_result();
$response['personalSummary'] = $budgetResult->fetch_assoc() ?: [];

// Group membership
$groupQuery = "
    SELECT eg.groupID, eg.groupName, ug.role, eg.inviteToken
    FROM USER_GROUP ug
    JOIN EXPENSE_GROUP eg ON ug.groupID = eg.groupID
    WHERE ug.userID = ?
";
$stmt = $conn->prepare($groupQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$groupResult = $stmt->get_result();
$groups = [];
while ($row = $groupResult->fetch_assoc()) {
    $groups[] = $row;
}
$response['groups'] = $groups;

echo json_encode($response);
?>
