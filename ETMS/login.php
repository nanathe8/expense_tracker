<?php
ob_start();
include 'db.php';  // Include database connection

session_start(); // Start the session to access session variables
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set Content-Type to application/json
header('Content-Type: application/json');

// Session timeout mechanism (optional)
$timeout_duration = 900; // 15 minutes in seconds

// Check if the session is still valid (not expired)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();     // Remove session variables
    session_destroy();   // Destroy the session
    echo json_encode(['status' => 'failure', 'status_code' => 440, 'message' => 'Session timed out. Please log in again.']);
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();  // Update last activity timestamp

// Check if form is submitted as a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input data
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Check if email and password are set
    if (!isset($inputData['email']) || !isset($inputData['password'])) {
        echo json_encode(['status' => 'failure', 'status_code' => 400, 'message' => 'Both fields are required']);
        exit;
    }

    $email = filter_var(trim($inputData['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($inputData['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'failure', 'status_code' => 400, 'message' => 'Both fields are required']);
        exit;
    }

    // Prepared statement to prevent SQL injection
    $sql = "SELECT * FROM USERS WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);  // "s" indicates the type is string (email)
    $stmt->execute();
    $result = $stmt->get_result();

 // Assuming $_SESSION['user_id'] is set after successful login
if ($result->num_rows > 0) {
    // Fetch user data
    $row = $result->fetch_assoc();

    // Verify password using password_verify
    if (password_verify($password, $row['password'])) {
        // Store user ID in the session
        $_SESSION['user_id'] = $row['userID'];  // Store userID in session
        $_SESSION['username'] = $row['name'];   // Optionally store the username

        // Respond with success and user_id
        echo json_encode([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Login successful',
            'user_id' => $row['userID'],  // Send user_id to Flutter
            'session_id' => session_id()  // Send session ID to Flutter
        ]);
    } else {
        echo json_encode(['status' => 'failure', 'status_code' => 401, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['status' => 'failure', 'status_code' => 404, 'message' => 'No user found with this email']);
}

    // Close the prepared statement
    $stmt->close();
} else {
    echo json_encode(['status' => 'failure', 'status_code' => 405, 'message' => 'Invalid request method']);
}

// Close the database connection
$conn->close();
?>
