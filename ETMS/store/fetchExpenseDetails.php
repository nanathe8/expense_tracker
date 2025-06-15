<?php
require 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['expense_id'])) {
    echo json_encode(["error" => "Missing expense_id"]);
    exit;
}

$expense_id = intval($_GET['expense_id']);

$sql = "SELECT * FROM expense_details WHERE expense_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $expense_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Expense not found"]);
}
?>
