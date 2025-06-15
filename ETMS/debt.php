<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// $_SESSION['userID'] = 3;

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$loggedInUserID = $_SESSION['userID'];

// Debts I owe others
$sqlIOwe = "
SELECT 
  es.shareID,
  e.description,
  u.name AS otherUserName,
  es.amount,
  es.paid,
  es.paidAt,
  0 AS paymentConfirmed
FROM EXPENSE_SHARE es
JOIN EXPENSE e ON es.expenseID = e.expenseID
JOIN USERS u ON e.paidBy = u.userID
WHERE es.userID = ? AND u.userID != ?
";

// Debts others owe me
$sqlOwesMe = "
SELECT
  es.shareID,
  e.description,
  u.name AS otherUserName,
  es.amount,
  es.paid,
  es.paidAt,
  0 AS paymentConfirmed
FROM EXPENSE_SHARE es
JOIN EXPENSE e ON es.expenseID = e.expenseID
JOIN USERS u ON es.userID = u.userID
WHERE e.paidBy = ? AND es.userID != ?
";

// Prepare and execute for debts I owe
$stmtIOwe = $conn->prepare($sqlIOwe);
$stmtIOwe->bind_param("ii", $loggedInUserID, $loggedInUserID);
$stmtIOwe->execute();
$resultIOwe = $stmtIOwe->get_result();
$debtsIOwe = $resultIOwe->fetch_all(MYSQLI_ASSOC);

// Prepare and execute for debts owed to me
$stmtOwesMe = $conn->prepare($sqlOwesMe);
$stmtOwesMe->bind_param("ii", $loggedInUserID, $loggedInUserID);
$stmtOwesMe->execute();
$resultOwesMe = $stmtOwesMe->get_result();
$debtsOwedToMe = $resultOwesMe->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'debtsIOwe' => $debtsIOwe,
    'debtsOwedToMe' => $debtsOwedToMe
]);

$stmtIOwe->close();
$stmtOwesMe->close();
$conn->close();
