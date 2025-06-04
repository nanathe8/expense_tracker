<?php
ob_start();
include 'db.php';  // Include database connection

session_start(); // Start the session to access session variables
header('Content-Type: application/json');

// // Check the request method
// if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//     echo json_encode(['status' => 'failure', 'status_code' => 405, 'message' => 'Invalid request method']);
//     exit;
// }

$inputData = json_decode(file_get_contents('php://input'), true);
$email = $inputData['email'] ?? '';
$password = $inputData['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'failure', 'status_code' => 400, 'message' => 'Both fields are required']);
    exit;
}

// Your database login logic
$sql = "SELECT * FROM USERS WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        // Successful login
        $_SESSION['userID'] = $row['userID'];
        $_SESSION['username'] = $row['name'];

        // Debugging: Ensure userID is set correctly
        error_log("User ID stored in session: " . $_SESSION['userID']);

        echo json_encode([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Login successful',
            'user_id' => $row['userID'],
            'session_id' => session_id()
            
        ]);
    } else {
        echo json_encode(['status' => 'failure', 'status_code' => 401, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['status' => 'failure', 'status_code' => 404, 'message' => 'No user found with this email']);
}

$stmt->close();
$conn->close();
?>
