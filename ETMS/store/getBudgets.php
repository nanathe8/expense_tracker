<?php
session_start();  // Start the session at the very beginning

include 'db.php';  // Include the database connection

// Debugging: Check the session data
error_log("Session ID: " . session_id());  // Log the session ID
error_log("Session data: " . print_r($_SESSION, true));  // Log the session data

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$user_id = $_SESSION['userID']; // Get the logged-in user ID

// Query to fetch personal budgets (groupID is NULL) for the given userID
$query = "SELECT * FROM BUDGET WHERE userID = ? AND groupID IS NULL";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare the query"]);
    exit();
}

$stmt->bind_param("i", $user_id);  // Bind the user_id to the query
$stmt->execute();

$result = $stmt->get_result();
if ($result === false) {
    echo json_encode(["status" => "error", "message" => "Query execution failed"]);
    exit();
}

$budgets = [];
while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;  // Add each row to the budgets array
}

// Return the budgets array (empty or populated)
echo json_encode(["status" => "success", "budgets" => $budgets]);

// Close the database connection
$stmt->close();
$conn->close();
?>
