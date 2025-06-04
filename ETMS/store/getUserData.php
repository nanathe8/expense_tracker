<?php
// Include your database connection file (if needed)
include('db_connection.php'); // Adjust path if necessary

// Get the session_id from the request
$session_id = $_GET['session_id'];

// Check if session_id is valid (e.g., check if it exists in your session or database)
if (!$session_id) {
    echo json_encode(['error' => 'Session ID is required']);
    exit;
}

// Your query to get user data from the database
$query = "SELECT * FROM users WHERE session_id = '$session_id' LIMIT 1";  // Adjust query as needed
$result = mysqli_query($conn, $query);

if ($result) {
    $user = mysqli_fetch_assoc($result);
    if ($user) {
        echo json_encode($user); // Return user data as JSON
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'Database query failed']);
}
?>
