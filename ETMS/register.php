<?php
session_start();
include 'db.php';  // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Read the JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the data exists and contains the necessary fields
    if (isset($data['username'], $data['email'], $data['password'])) {
        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'failure', 'message' => 'All fields are required. Please make sure to fill in the username, email, and password.']);
            exit;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'failure', 'message' => 'Invalid email format. Please enter a valid email address (e.g., user@example.com).']);
            exit;
        }

        // Check if password meets minimum length (e.g., 8 characters)
        if (strlen($password) < 8) {
            echo json_encode(['status' => 'failure', 'message' => 'Password must be at least 8 characters long.']);
            exit;
        }

        // Hash the password using Argon2i
        $hashedPassword = password_hash($password, PASSWORD_ARGON2I);

        // Check if the email already exists
        $stmt_check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'failure', 'message' => 'Email already exists. Please use a different email address.']);
            exit;
        }

        // Insert user data into the database
        $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt_insert->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User registered successfully. You can now log in.']);
        } else {
            // If insertion fails, provide specific error message
            echo json_encode(['status' => 'failure', 'message' => 'Registration failed due to a server error. Please try again later.']);
        }

        // Close the prepared statement
        $stmt_insert->close();
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Missing data. Please provide username, email, and password.']);
        exit;
    }
} else {
    // If request method is not POST, return error message
    echo json_encode(['status' => 'failure', 'message' => 'Invalid request method. Please submit the form via POST.']);
    exit;
}
?>
