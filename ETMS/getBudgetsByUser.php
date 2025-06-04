<?php
// Database connection (update with your actual database connection)
include 'db.php';

$userID = $_POST['userID'];  // Get the userID from POST data

// Query to fetch budgets with the user's ID and groupID is null
$query = "SELECT * FROM budget WHERE userID = ? AND groupID IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$budgets = array();
while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;
}

// Make sure to always return valid JSON
header('Content-Type: application/json');
echo json_encode(['budgets' => $budgets]);
?>
