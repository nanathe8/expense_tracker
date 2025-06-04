<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the category name and icon
    $category_name = $_POST['category_name'];
    $icon = $_POST['icon'];

    // Insert the new category into the database
    $sql = "INSERT INTO Categories (userID, categoryName, icon) VALUES ('$user_id', '$category_name', '$icon')";
    
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Category added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add category"]);
    }
}
?>
