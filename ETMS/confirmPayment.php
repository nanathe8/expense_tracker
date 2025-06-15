<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$shareID = intval($_POST['shareID'] ?? 0);
if ($shareID <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid shareID']));
}

$stmt = $conn->prepare("
  UPDATE EXPENSE_SHARE es
  JOIN EXPENSE e USING (expenseID)
  SET es.paymentConfirmed = 1
  WHERE es.shareID = ? AND e.paidBy = ? AND es.paid = 1 AND es.paymentConfirmed = 0
");
$stmt->bind_param('ii', $shareID, $_SESSION['userID']);
$stmt->execute();

if ($stmt->affected_rows) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nothing updated or unauthorized']);
}
$stmt->close();
$conn->close();
