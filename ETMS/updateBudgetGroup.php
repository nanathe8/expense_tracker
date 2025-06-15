<?php
include 'db.php';
session_start();

// 1. Check user session
if (!isset($_SESSION['userID'])) {
    error_log('Session not set: ' . print_r($_SESSION, true));
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];

// // 2. If no category_id provided, return list of groups for the user
// if (empty($_POST['category_id'])) {
//     $stmt = $conn->prepare("SELECT eg.groupID, eg.groupName 
//                             FROM EXPENSE_GROUP eg 
//                             JOIN USER_GROUP ug ON eg.groupID = ug.groupID 
//                             WHERE ug.userID = ?");
//     $stmt->bind_param("i", $userID);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     $groups = [];
//     while ($row = $result->fetch_assoc()) {
//         $groups[] = $row;
//     }

//     echo json_encode(['status' => 'success', 'groups' => $groups]);
//     exit();
// }

// 3. Get groupID from POST
$groupID = isset($_POST['groupID']) ? (int)$_POST['groupID'] : null;
if (!$groupID) {
    echo json_encode(['status' => 'failure', 'message' => 'Group ID is required.']);
    exit();
}

// 4. Get total income for the group via BUDGET
$incomeQuery = "
    SELECT SUM(i.amount) AS totalIncome
    FROM INCOME i
    JOIN BUDGET b ON i.budgetID = b.budgetID
    WHERE b.groupID = ?
";
$stmt_income = $conn->prepare($incomeQuery);
$stmt_income->bind_param("i", $groupID);
$stmt_income->execute();
$result_income = $stmt_income->get_result();
$row_income = $result_income->fetch_assoc();
$totalIncome = isset($row_income['totalIncome']) ? (float)$row_income['totalIncome'] : 0.0;
$stmt_income->close();

// 5. Get total expenses for the group via BUDGET
$expenseQuery = "
    SELECT SUM(e.amount) AS totalExpense
    FROM EXPENSE e
    JOIN BUDGET b ON e.budgetID = b.budgetID
    WHERE b.groupID = ?
";
$stmt_expense = $conn->prepare($expenseQuery);
$stmt_expense->bind_param("i", $groupID);
$stmt_expense->execute();
$result_expense = $stmt_expense->get_result();
$row_expense = $result_expense->fetch_assoc();
$totalExpenses = isset($row_expense['totalExpense']) ? (float)$row_expense['totalExpense'] : 0.0;
$stmt_expense->close();

// 6. Get the budgetAmount (assumes one budget per group)
$budgetQuery = "SELECT budgetID, budgetAmount FROM BUDGET WHERE groupID = ?";
$stmt_budget = $conn->prepare($budgetQuery);
$stmt_budget->bind_param("i", $groupID);
$stmt_budget->execute();
$result_budget = $stmt_budget->get_result();
$row_budget = $result_budget->fetch_assoc();
$budgetAmount = isset($row_budget['budgetAmount']) ? (float)$row_budget['budgetAmount'] : 0.0;
$budgetID = isset($row_budget['budgetID']) ? (int)$row_budget['budgetID'] : null;
$stmt_budget->close();

if (!$budgetID) {
    echo json_encode(['status' => 'failure', 'message' => 'Budget not found for the group.']);
    exit();
}

// 7. Calculate balance
$balance = $budgetAmount - $totalExpenses;
$balance = round($balance, 2); // optional rounding

// 8. Update budget table
$updateQuery = "UPDATE BUDGET SET totalIncome = ?, totalExpenses = ?, balance = ? WHERE budgetID = ?";
$stmt_update = $conn->prepare($updateQuery);
if ($stmt_update === false) {
    error_log("SQL error preparing update: " . $conn->error);
    echo json_encode(['status' => 'failure', 'message' => 'Database error (prepare failed).']);
    exit();
}
$stmt_update->bind_param("dddi", $totalIncome, $totalExpenses, $balance, $budgetID);

if ($stmt_update->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Group budget updated successfully.',
        'totalIncome' => $totalIncome,
        'totalExpenses' => $totalExpenses,
        'budgetAmount' => $budgetAmount,
        'balance' => $balance
    ]);
} else {
    error_log("Error executing update query: " . $stmt_update->error);
    echo json_encode(['status' => 'failure', 'message' => 'Failed to update group budget.']);
}

$stmt_update->close();
$conn->close();
?>
