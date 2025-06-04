<?php
include 'db.php';

// Example: Fetching categories
$sql = "SELECT * FROM CATEGORY";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;  // Add each category to the array
}

header('Content-Type: application/json');  // Ensure the content type is JSON
echo json_encode(['categories' => $categories]);  // Return JSON response
?>
