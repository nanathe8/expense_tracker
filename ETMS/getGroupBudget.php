<?php
include 'db.php';  // Your DB connection file

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['groupID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'groupID is required']);
    exit();
}

$groupID = intval($data['groupID']);

try {
    // Query to get the latest budget for the group (or adjust logic if multiple budgets)
    $stmt = $pdo->prepare("
        SELECT budgetName, budgetAmount, totalExpenses, balance
        FROM BUDGET
        WHERE groupID = :groupID
        ORDER BY startDate DESC
        LIMIT 1
    ");

    $stmt->execute(['groupID' => $groupID]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($budget) {
        echo json_encode(['status' => 'success', 'budget' => $budget]);
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'No budget found for this group']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'failure', 'message' => 'Error: ' . $e->getMessage()]);
}
