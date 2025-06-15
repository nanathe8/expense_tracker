<?php
include 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User not logged in']);
    exit();
}

$userID = $_SESSION['userID'];

try {
    // Step 1: Get all groupIDs for the user
    $stmtGroupIDs = $conn->prepare("SELECT groupID FROM USER_GROUP WHERE userID = ?");
    $stmtGroupIDs->bind_param("i", $userID);
    $stmtGroupIDs->execute();
    $resultGroupIDs = $stmtGroupIDs->get_result();

    if ($resultGroupIDs->num_rows === 0) {
        echo json_encode(['status' => 'failure', 'message' => 'User is not in any group']);
        exit();
    }

    $groupsData = [];

    // Step 2: For each groupID, fetch group details and expenses
    while ($row = $resultGroupIDs->fetch_assoc()) {
        $groupID = $row['groupID'];

        // Get group info
        $stmtGroup = $conn->prepare("SELECT * FROM EXPENSE_GROUP WHERE groupID = ?");
        $stmtGroup->bind_param("i", $groupID);
        $stmtGroup->execute();
        $resultGroup = $stmtGroup->get_result();
        $group = $resultGroup->fetch_assoc();
        $stmtGroup->close();

        // Get expenses for the group
        $stmtExpenses = $conn->prepare("
            SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description
            FROM EXPENSE e
            JOIN CATEGORY c ON e.categoryID = c.categoryID
            JOIN BUDGET b ON e.budgetID = b.budgetID
            WHERE b.groupID = ?
            ORDER BY e.date DESC
        ");
        if (!$stmtExpenses) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmtExpenses->bind_param("i", $groupID);
        $stmtExpenses->execute();
        $resultExpenses = $stmtExpenses->get_result();

        $expenses = [];
        while ($exp = $resultExpenses->fetch_assoc()) {
            $expenses[] = $exp;
        }
        $stmtExpenses->close();

        // Optionally fetch members if you want
        // $stmtMembers = $conn->prepare("SELECT ... FROM USER_GROUP JOIN USER ... WHERE groupID = ?");
        // ...

        $groupsData[] = [
            'group' => $group,
            'expenses' => $expenses,
            // 'members' => $members  // add if needed
        ];
    }

    echo json_encode(['status' => 'success', 'groups' => $groupsData]);

} catch (Exception $e) {
    echo json_encode(['status' => 'failure', 'message' => 'Error: ' . $e->getMessage()]);
}
