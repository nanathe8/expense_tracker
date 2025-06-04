<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];

// Fetch budgetID
$budgetID = 0;
$budgetQuery = "SELECT budgetID FROM budget WHERE userID = ? AND groupID IS NULL";
$stmt_budget = $conn->prepare($budgetQuery);
$stmt_budget->bind_param("i", $user_id);
$stmt_budget->execute();
$result_budget = $stmt_budget->get_result();
if ($row_budget = $result_budget->fetch_assoc()) {
    $budgetID = (int)$row_budget['budgetID'];
}
$stmt_budget->close();

// Early exit if no budget
if ($budgetID === 0) {
    echo json_encode([
        "error" => "No budget found for user",
        "user_id" => $user_id
    ]);
    exit();
}

$totalIncome = 0.0;
$incomeQuery = "SELECT SUM(totalIncome) AS totalIncome FROM budget WHERE budgetID = ? AND groupID IS NULL";
$stmt_income = $conn->prepare($incomeQuery);
$stmt_income->bind_param("i", $budgetID);
$stmt_income->execute();
$result_income = $stmt_income->get_result();

if ($row_income = $result_income->fetch_assoc()) {
    $totalIncome = (float)$row_income['totalIncome'];
    error_log("Fetched totalIncome: $totalIncome");
} else {
    error_log("Income fetch failed.");
}
$stmt_income->close();


// Fetch total expenses
$totalExpenses = 0.0;
$expenseQuery = "SELECT SUM(totalExpenses) AS totalExpense FROM budget WHERE budgetID = ? AND groupID IS NULL";
$stmt_expense = $conn->prepare($expenseQuery);
$stmt_expense->bind_param("i", $budgetID);
$stmt_expense->execute();
$result_expense = $stmt_expense->get_result();
if ($row_expense = $result_expense->fetch_assoc()) {
    $totalExpenses = (float)$row_expense['totalExpense'];
}
$stmt_expense->close();

// Fetch transactions (income + expense) with categories
$sql_transactions = "
    SELECT 
        e.date, 
        'Expense' AS type, 
        e.amount, 
        c.categoryName AS category,
        e.description
    FROM expense e
    LEFT JOIN category c ON e.categoryID = c.categoryID
    WHERE e.budgetID = ?

    UNION

    SELECT 
        i.date, 
        'Income' AS type, 
        i.amount, 
        NULL AS category,
        i.source AS description
    FROM income i
    WHERE i.budgetID = ?

ORDER BY date DESC";

$stmt_trans = $conn->prepare($sql_transactions); 
$stmt_trans->bind_param("ii", $budgetID, $budgetID);
$stmt_trans->execute();
$result = $stmt_trans->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt_trans->close();

// Prepare response
$response = [
    'total_income' => $totalIncome,
    'total_expenses' => $totalExpenses,
    'remaining_balance' => $totalIncome - $totalExpenses,
    'transactions' => $transactions,  // add this line
    'debug' => [
        'user_id' => $user_id,
        'budgetID' => $budgetID
    ]
];



header('Content-Type: application/json');
echo json_encode($response);
?>
