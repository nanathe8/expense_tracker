<?php
// Always declare content type first
header('Content-Type: application/json');

// Turn off HTML error display (prevent <br />, warnings, etc.)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $budget_id = $_POST['budget_id'] ?? 0;

    // Get total expenses
    $expense_sql = "SELECT SUM(amount) AS totalExpense FROM EXPENSE WHERE budgetID = ? AND groupID IS NULL";
    $stmt_expense = $conn->prepare($expense_sql);
    $stmt_expense->bind_param("i", $budget_id);
    $stmt_expense->execute();
    $result_expense = $stmt_expense->get_result();
    $data_expense = $result_expense->fetch_assoc();
    $totalExpense = isset($data_expense['totalExpense']) ? (float)$data_expense['totalExpense'] : 0.0;

    // Get total income
    $income_sql = "SELECT SUM(amount) AS totalIncome FROM INCOME WHERE budgetID = ?";
    $stmt_income = $conn->prepare($income_sql);
    $stmt_income->bind_param("i", $budget_id);
    $stmt_income->execute();
    $result_income = $stmt_income->get_result();
    $data_income = $result_income->fetch_assoc();
    $totalIncome = isset($data_income['totalIncome']) ? (float)$data_income['totalIncome'] : 0.0;

    // Return JSON
    echo json_encode([
        'status' => 'success',
        'totalExpense' => $totalExpense,
        'totalIncome' => $totalIncome
    ]);
}
?>
