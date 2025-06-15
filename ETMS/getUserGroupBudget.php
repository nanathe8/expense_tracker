<?php
header('Content-Type: application/json');
include 'db.php';

$userID = $_POST['userID'] ?? null;

if (!$userID) {
    echo json_encode(['success' => false, 'message' => 'userID is required']);
    exit;
}

// Step 1: Find the groupID and role the user belongs to
$sqlGroup = "SELECT groupID, role FROM USER_GROUP WHERE userID = ? LIMIT 1";
$stmt = $conn->prepare($sqlGroup);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if (!$groupRow = $result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'User not in any group']);
    exit;
}

$groupID = $groupRow['groupID'];
$role = $groupRow['role'];

// Step 2: Find the budget for that group
$sqlBudget = "
    SELECT b.*, g.groupName, g.groupDescription
    FROM BUDGET b
    JOIN EXPENSE_GROUP g ON b.groupID = g.groupID
    WHERE b.groupID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sqlBudget);
$stmt->bind_param("i", $groupID);
$stmt->execute();
$budgetResult = $stmt->get_result();

if (!$budget = $budgetResult->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'No budget found for this group']);
    exit;
}

// Success: return all info
echo json_encode([
    'success' => true,
    'role' => $role,
    'group' => [
        'groupID' => $groupID,
        'groupName' => $budget['groupName'],
        'groupDescription' => $budget['groupDescription'],
    ],
    'budget' => [
        'budgetID' => $budget['budgetID'],
        'budgetName' => $budget['budgetName'],
        'budgetAmount' => $budget['budgetAmount'],
        'startDate' => $budget['startDate'],
        'endDate' => $budget['endDate'],
        'totalIncome' => $budget['totalIncome'],
        'totalExpenses' => $budget['totalExpenses'],
        'balance' => $budget['balance'],
    ]
]);
