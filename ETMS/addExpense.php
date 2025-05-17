<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debugging line: Check if category_id is passed in POST request
    if (isset($_POST['category_id'])) {
        echo "Category ID: " . $_POST['category_id'];  // Debugging line
    }
    
    // Get form inputs
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $group_id = $_POST['group_id'];  // If it's a shared expense, this is not null
    $budget_id = $_POST['budget_id'];  // If it's a personal expense, this is not null

    // Check if the budget exists for personal expenses (if budget_id is set)
    if ($budget_id) {
        $budget_check_sql = "SELECT * FROM BUDGET WHERE budgetID = ? AND userID = ?";
        $stmt = $conn->prepare($budget_check_sql);
        $stmt->bind_param("ii", $budget_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo "<script>alert('No budget found for the specified budget ID');</script>";
            exit;
        }
    }

    // Handle file upload for receipt
    $receipt_image = '';
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        $target_dir = "uploads/";  // Make sure the uploads folder exists
        $target_file = $target_dir . basename($_FILES["receipt_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is a valid image type (jpg, jpeg, png, gif)
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
                $receipt_image = $target_file;  // Save the path to the image
            } else {
                echo "Error uploading receipt image.";
                exit;
            }
        } else {
            echo "Only JPG, JPEG, PNG & GIF files are allowed.";
            exit;
        }
    }

    // Insert expense data into the Expense table using prepared statements
    if ($category_id && $amount && $description && $date) {
        // Insert into EXPENSE table
        $stmt = $conn->prepare("INSERT INTO EXPENSE (userID, categoryID, amount, description, date, groupID, budgetID, receipt_image) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiiis", $user_id, $category_id, $amount, $description, $date, $group_id, $budget_id, $receipt_image);

        if ($stmt->execute()) {
            echo "<script>alert('Expense added successfully!');</script>";
        } else {
            echo "<script>alert('Error executing query: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.');</script>";
    }
}
?>
