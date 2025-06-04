<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');  // Redirect to login page if not logged in
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Fetch expenses from the database for the logged-in user
$sql = "SELECT e.expenseID, c.categoryName, e.amount, e.description, e.date 
        FROM Expense e
        JOIN Category c ON e.categoryID = c.categoryID
        WHERE e.userID = '$user_id'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Expenses</title>
</head>
<body>

<h2>Your Expenses</h2>

<table border="1">
    <tr>
        <th>Category</th>
        <th>Amount</th>
        <th>Description</th>
        <th>Date</th>
    </tr>

    <?php
    // Display the expenses in a table
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['categoryName'] . "</td>";
        echo "<td>" . $row['amount'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "</tr>";
    }
    ?>
</table>

</body>
</html>
