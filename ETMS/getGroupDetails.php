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
    $stmtGroupIDs = $conn->prepare("SELECT groupID FROM USER_GROUP WHERE userID = ?");
    $stmtGroupIDs->bind_param("i", $userID);
    $stmtGroupIDs->execute();
    $resultGroupIDs = $stmtGroupIDs->get_result();

    if ($resultGroupIDs->num_rows === 0) {
        echo json_encode(['status' => 'failure', 'message' => 'User is not in any group']);
        exit();
    }

    $groupsData = [];

while ($row = $resultGroupIDs->fetch_assoc()) {
    $groupID = $row['groupID'];

    // Fetch budgets for this group
    $stmtBudgets = $conn->prepare("SELECT * FROM budget WHERE groupID = ?");
    $stmtBudgets->bind_param("i", $groupID);
    $stmtBudgets->execute();
    $resultBudgets = $stmtBudgets->get_result();
    $budgets = $resultBudgets->fetch_all(MYSQLI_ASSOC);
    $stmtBudgets->close();

    // Group info
    $stmtGroup = $conn->prepare("SELECT * FROM EXPENSE_GROUP WHERE groupID = ?");
    $stmtGroup->bind_param("i", $groupID);
    $stmtGroup->execute();
    $resultGroup = $stmtGroup->get_result();
    $group = $resultGroup->fetch_assoc();
    $stmtGroup->close();

    // Group members
    $stmtMembers = $conn->prepare("
        SELECT U.userID, U.name, UG.role
        FROM USER_GROUP UG
        JOIN USERS U ON UG.userID = U.userID
        WHERE UG.groupID = ?
    ");
    $stmtMembers->bind_param("i", $groupID);
    $stmtMembers->execute();
    $resultMembers = $stmtMembers->get_result();
    $members = $resultMembers->fetch_all(MYSQLI_ASSOC);
    $stmtMembers->close();

    // Group expenses
    $stmtExpenses = $conn->prepare("
        SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description,
            e.receiptImage, u.name AS addedBy
        FROM EXPENSE e
        JOIN CATEGORY c ON e.categoryID = c.categoryID
        JOIN BUDGET b ON e.budgetID = b.budgetID
        JOIN USERS u ON e.paidBy = u.userID
        WHERE b.groupID = ? AND b.deleted_at IS NULL
        ORDER BY e.date DESC
    ");

    $stmtExpenses->bind_param("i", $groupID);
    $stmtExpenses->execute();
    $resultExpenses = $stmtExpenses->get_result();
    $expenses = $resultExpenses->fetch_all(MYSQLI_ASSOC);
    $stmtExpenses->close();

    $groupsData[] = [
        'group' => $group,
        'members' => $members,
        'expenses' => $expenses,
        'budgets' => $budgets  // <-- add budgets here!
    ];
}
    echo json_encode(['status' => 'success', 'groups' => $groupsData]);

} catch (Exception $e) {
    echo json_encode(['status' => 'failure', 'message' => $e->getMessage()]);
}
