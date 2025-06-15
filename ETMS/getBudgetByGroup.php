<?php
include 'db.php';

$groupID = $_POST['groupID'];  // Get groupID from POST data

// Fetch budgets linked to the group (groupID NOT NULL)
$query = "SELECT * FROM budget WHERE groupID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $groupID);
$stmt->execute();
$result = $stmt->get_result();

$budgets = array();
while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['budgets' => $budgets]);
?>
