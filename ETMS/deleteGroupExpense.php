<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Not logged in']);
    exit;
}

if (!isset($_POST['expenseID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing expenseID']);
    exit;
}

$expenseID = intval($_POST['expenseID']);
$userID = intval($_SESSION['userID']);

// Step 1: Find groupID for this expense
$query = "SELECT B.groupID 
          FROM EXPENSE E 
          JOIN BUDGET B ON E.budgetID = B.budgetID 
          WHERE E.expenseID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $expenseID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Expense not found']);
    exit;
}

$row = $result->fetch_assoc();
$groupID = $row['groupID'];

// Step 2: Check if user is admin in this group
$query2 = "SELECT role FROM USER_GROUP WHERE userID = ? AND groupID = ?";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("ii", $userID, $groupID);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User is not a member of this group']);
    exit;
}

$row2 = $result2->fetch_assoc();
if ($row2['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Admin access required']);
    exit;
}

// Step 1: Delete related records from expense_share
$query_delete_share = "DELETE FROM expense_share WHERE expenseID = ?";
$stmt_delete_share = $conn->prepare($query_delete_share);
$stmt_delete_share->bind_param("i", $expenseID);

if (!$stmt_delete_share->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete related records from expense_share']);
    exit;
}

// Step 2: Now delete the expense from the EXPENSE table
$query_delete_expense = "DELETE FROM EXPENSE WHERE expenseID = ?";
$stmt_delete_expense = $conn->prepare($query_delete_expense);
$stmt_delete_expense->bind_param("i", $expenseID);

if ($stmt_delete_expense->execute()) {
    if ($stmt_delete_expense->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Expense deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Expense not found or already deleted']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete expense']);
}

$stmt->close();
$stmt2->close();
$stmt_delete_share->close();
$stmt_delete_expense->close();
$conn->close();
?>
