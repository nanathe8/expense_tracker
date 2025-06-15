<?php
require 'db.php';

header('Content-Type: application/json');

$sql = "SELECT id, date, category, description, amount, type FROM expense ORDER BY date DESC";
$result = mysqli_query($conn, $sql);

$transactions = [];

while ($row = mysqli_fetch_assoc($result)) {
    $transactions[] = [
        "id" => $row['id'],
        "date" => $row['date'],
        "category" => $row['category'],
        "description" => $row['description'],
        "amount" => (float)$row['amount'],
        "type" => $row['type']
    ];
}

echo json_encode($transactions);
?>
