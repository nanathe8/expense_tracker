<?php
header("Content-Type: application/json");

$servername = "localhost"; // Your database server
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "hyta"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['userID'])) {
    $userID = $_GET['userID']; // Get userID from the URL

    // Prepare the SQL statement
    $sql = "SELECT name FROM USERS WHERE userID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID); // Bind the userID parameter
    $stmt->execute();
    $stmt->store_result();
    
    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($name);
        $stmt->fetch();
        
        // Create an associative array to hold the user data
        $userData = [
            "name" => $name,
        ];
        
        echo json_encode($userData); // Return user data as JSON
    } else {
        echo json_encode(["error" => "User not found"]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["error" => "User ID is required"]);
}

$conn->close();
?>
