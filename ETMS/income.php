<?php
include 'db.php'; // Include database connection
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID

// Get the budgetID from the BUDGET table based on the user's ID
$sql = "SELECT budgetID FROM BUDGET WHERE userID = '$user_id' ORDER BY dateStart DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $budgetID = $row['budgetID'];
} else {
    echo json_encode(["status" => "error", "message" => "No budget found for this user."]);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $amount = $_POST['amount'];
    $source = $_POST['source']; // Get income source (e.g., Salary, Freelance, etc.)
    $date = $_POST['date'];

    // Validate inputs
    if (empty($amount) || empty($source) || empty($date)) {
        echo json_encode(["status" => "error", "message" => "Please fill all the fields"]);
        exit();
    }

    // Insert data into the INCOME table
    $sql = "INSERT INTO INCOME (userID, amount, source, date, budgetID) 
            VALUES ('$user_id', '$amount', '$source', '$date', '$budgetID')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Income added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding income: " . $conn->error]);
    }
}
?>
