<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$userID = $_SESSION['userID'] ?? null;
if (!$userID) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}


$stmt = $conn->prepare("
    SELECT e.expenseID, e.amount, e.date, c.categoryName, e.description, e.receiptImage
    FROM EXPENSE e
    JOIN BUDGET b ON e.budgetID = b.budgetID
    JOIN CATEGORY c ON e.categoryID = c.categoryID
    WHERE b.userID = ? AND b.deleted_at IS NULL
    ORDER BY e.date DESC
");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();


$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = [
        'expenseID' => $row['expenseID'],
        'amount' => $row['amount'],
        'date' => $row['date'],
        'category' => $row['categoryName'],
        'description' => $row['description'],
        'receiptImage' => $row['receiptImage'] ? "http://localhost/PSM1/ETMS/uploads/" . $row['receiptImage'] : null
    ];
}

echo json_encode(['expenses' => $expenses]);

$stmt->close();
$conn->close();
?>
