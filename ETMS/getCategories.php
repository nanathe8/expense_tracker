<?php
session_start();
include 'db.php'; // Include the database connection file

// Check if the connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// SQL query to get categories
$sql = "SELECT categoryID, categoryName FROM Category";
$result = mysqli_query($conn, $sql);

// Check if we have any categories
if (mysqli_num_rows($result) > 0) {
    // Create an array to hold categories
    $categories = array();

    // Fetch the categories and store them in the array
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }

    // Return the categories as JSON
    echo json_encode(array('categories' => $categories));
} else {
    // If no categories, return an empty array
    echo json_encode(array('categories' => []));
}

// Close the connection
mysqli_close($conn);
?>
