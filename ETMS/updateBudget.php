<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    error_log('Session not set: ' . print_r($_SESSION, true));
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];
$budgetID = isset($_POST['budgetID']) ? (int)$_POST['budgetID'] : null;

if (!$budgetID) {
    echo json_encode(['status' => 'failure', 'message' => 'Budget ID is required.']);
    exit;
}

// 1. Get total income
$incomeQuery = "SELECT SUM(amount) AS totalIncome FROM income WHERE budgetID = ?";
$stmt_income = $conn->prepare($incomeQuery);
$stmt_income->bind_param("i", $budgetID);
$stmt_income->execute();
$result_income = $stmt_income->get_result();
$row_income = $result_income->fetch_assoc();
$totalIncome = isset($row_income['totalIncome']) ? (float)$row_income['totalIncome'] : 0.0;
$stmt_income->close();

// 2. Get total expenses
$expenseQuery = "SELECT SUM(amount) AS totalExpense FROM expense WHERE budgetID = ?";
$stmt_expense = $conn->prepare($expenseQuery);
$stmt_expense->bind_param("i", $budgetID);
$stmt_expense->execute();
$result_expense = $stmt_expense->get_result();
$row_expense = $result_expense->fetch_assoc();
$totalExpenses = isset($row_expense['totalExpense']) ? (float)$row_expense['totalExpense'] : 0.0;
$stmt_expense->close();

// 3. Get original budget amount
$balanceQuery = "SELECT budgetAmount FROM budget WHERE budgetID = ?";
$stmt_balance = $conn->prepare($balanceQuery);
$stmt_balance->bind_param("i", $budgetID);
$stmt_balance->execute();
$result_balance = $stmt_balance->get_result();
$row_balance = $result_balance->fetch_assoc();
$budgetAmount = isset($row_balance['budgetAmount']) ? (float)$row_balance['budgetAmount'] : 0.0;
$stmt_balance->close();

// 4. Calculate balance
$balance = $totalIncome - $totalExpenses;
$balance = (float)$balance;

// Debugging logs
error_log("Updating Budget - Budget ID: $budgetID, Total Expense: $totalExpenses, Balance: $balance");

// 5. Update budget
$updateQuery = "UPDATE budget SET totalExpenses = ?, balance = ? WHERE budgetID = ?";
$stmt_update = $conn->prepare($updateQuery);
if ($stmt_update === false) {
    error_log("SQL error preparing update: " . $conn->error);
    echo json_encode(['status' => 'failure', 'message' => 'Database error (update budget).']);
    exit();
}
$stmt_update->bind_param("ddi", $totalExpenses, $balance, $budgetID);

if ($stmt_update->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Budget updated successfully.',
        'totalIncome' => $totalIncome,
        'totalExpenses' => $totalExpenses,
        'budgetAmount' => $budgetAmount,
        'balance' => $balance
    ]);
} else {
    error_log("Error executing update query: " . $stmt_update->error);
    echo json_encode(['status' => 'failure', 'message' => 'Failed to update budget.']);
}

$stmt_update->close();
$conn->close();
?>
