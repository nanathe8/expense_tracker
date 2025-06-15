<?php
include 'db.php';
$userID = $_POST['userID'];

$query = "SELECT EG.groupID, EG.groupName 
          FROM EXPENSE_GROUP EG
          JOIN USER_GROUP UG ON EG.groupID = UG.groupID
          WHERE UG.userID = ? AND UG.role = 'admin'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$groups = [];
while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
}

echo json_encode($groups);
?>
