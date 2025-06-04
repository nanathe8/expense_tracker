<?php
session_start();
include 'db.php'; // your database connection

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$userID = $_SESSION['userID'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['budgetID'])) {
    echo json_encode(['status' => 'error', 'message' => 'budgetID is required']);
    exit;
}

$budgetID = intval($data['budgetID']);

try {
    $stmt = $conn->prepare("UPDATE budget SET deleted_at = NULL WHERE budgetID = ? AND userID = ? AND groupID IS NULL");
    $stmt->bind_param("ii", $budgetID, $userID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Budget recovered successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Budget not found or already active']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
