<?php
// Include database connection
include 'db.php';
session_start();
error_log("Session ID: " . session_id());  // Log session ID to track the active session


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');  // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Total Income
$sql_income = "
    SELECT SUM(i.amount) AS total_income
    FROM INCOME i
    JOIN BUDGET b ON i.budgetID = b.budgetID
    WHERE b.userID = '$user_id'
";
$result_income = $conn->query($sql_income);
$row_income = $result_income->fetch_assoc();
$total_income = $row_income['total_income'] ?? 0.0;  // Default to 0.0 if null

// Fetch Total Expenses
$sql_expenses = "
    SELECT SUM(e.amount) AS total_expenses
    FROM EXPENSE e
    JOIN BUDGET b ON e.budgetID = b.budgetID
    WHERE b.userID = '$user_id'
";
$result_expenses = $conn->query($sql_expenses);
$row_expenses = $result_expenses->fetch_assoc();
$total_expenses = $row_expenses['total_expenses'] ?? 0.0;  // Default to 0.0 if null

// Ensure remaining balance doesn't throw an error
$remaining_balance = ($total_income ?? 0) - ($total_expenses ?? 0);

// Fetch Transactions (both income and expenses)
$sql_transactions = "
    SELECT t.date, t.type, t.amount, t.description, c.categoryName
    FROM (SELECT * FROM INCOME WHERE userID = '$user_id' 
          UNION 
          SELECT * FROM EXPENSE WHERE userID = '$user_id') t
    LEFT JOIN CATEGORY c ON t.categoryID = c.categoryID
    ORDER BY t.date DESC
";
$result_transactions = $conn->query($sql_transactions);
$transactions_by_date = [];
while ($row = $result_transactions->fetch_assoc()) {
    $date = date('D, d M Y', strtotime($row['date']));  // Format date
    $transactions_by_date[$date][] = $row;  // Group by date
}

// Return the data as JSON
$response = [
    'total_income' => $total_income,
    'total_expenses' => $total_expenses,
    'remaining_balance' => $remaining_balance,
    'transactions' => $transactions_by_date
];

// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
