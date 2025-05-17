<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");


$servername = "localhost";
$username = "root";
$password = "";
$database = "hypespend_tracker";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['status' => 'failure', 'message' => 'Connection failed: ' . $conn->connect_error]));
    // die("Connection failed: " . $conn->connect_error);
}
?>