<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// Step 1: Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userID = $_SESSION['userID'];
$showDeleted = isset($_GET['showDeleted']) && $_GET['showDeleted'] === 'true';

// Step 2: Build SQL query with dynamic deleted_at condition
$deletedCondition = $showDeleted ? "IS NOT NULL" : "IS NULL";

$sql = "
    SELECT b.*, g.groupName, ug.role
    FROM BUDGET b
    JOIN EXPENSE_GROUP g ON b.groupID = g.groupID
    JOIN USER_GROUP ug ON ug.groupID = b.groupID AND ug.userID = ?
    WHERE b.deleted_at $deletedCondition
";

// Step 3: Prepare and execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

// Step 4: Fetch results
$budgets = [];
while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;
}

// Step 5: Respond
echo json_encode(['success' => true, 'budgets' => $budgets]);

$stmt->close();
$conn->close();
?>
