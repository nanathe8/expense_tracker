<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Destroy the session
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Optionally, remove the session cookie (to make sure the user can't use the old session)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Return a success response
echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully"
]);
?>
