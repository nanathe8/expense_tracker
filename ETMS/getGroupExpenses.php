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
    // Query to get all expenses for the group
    $stmt = $pdo->prepare("
        SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description
        FROM EXPENSE e
        JOIN CATEGORY c ON e.categoryID = c.categoryID
        JOIN BUDGET b ON e.budgetID = b.budgetID
        WHERE b.groupID = :groupID
        ORDER BY e.date DESC
    ");

    $stmt->execute(['groupID' => $groupID]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'expenses' => $expenses]);

} catch (Exception $e) {
    echo json_encode(['status' => 'failure', 'message' => 'Error: ' . $e->getMessage()]);
}
