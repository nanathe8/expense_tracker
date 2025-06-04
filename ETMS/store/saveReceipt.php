<?php
include 'db.php';  // Make sure your database connection is included

// Get the form data sent via AJAX
$amount = $_POST['amount'];
$merchant = $_POST['merchant'];
$date = $_POST['date'];
$receipt_image = $_POST['receipt_image'];  // Path of the uploaded receipt

// Insert the data into the Expense table
$sql = "INSERT INTO Expense (userID, categoryID, amount, description, date, receiptImage)
        VALUES ('$user_id', '$category_id', '$amount', '$merchant', '$date', '$receipt_image')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'Receipt data saved successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error saving data: ' . $conn->error]);
}
?>
