<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$shareID = intval($data['shareID'] ?? 0);

if ($shareID <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid shareID']));
}

$stmt = $conn->prepare("
  UPDATE EXPENSE_SHARE 
  SET paid = 1, paidAt = NOW() 
  WHERE shareID = ? AND userID = ?
");
$stmt->bind_param('ii', $shareID, $_SESSION['userID']);
$stmt->execute();

if ($stmt->affected_rows) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nothing updated']);
}
$stmt->close();
$conn->close();
