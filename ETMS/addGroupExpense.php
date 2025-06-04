<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];

// Get form inputs
$category_id = $_POST['category_id'] ?? '';
$amount = $_POST['amount'] ?? '';
$description = $_POST['description'] ?? '';
$date = $_POST['date'] ?? '';
$group_id = $_POST['group_id'] ?? '';  // Group ID
$budget_id = $_POST['budget_id'] ?? '';  // Group budget ID
$income_id = $_POST['income_id'] ?? '';  // Income ID from the group

// Validate inputs
if (empty($category_id) || empty($amount) || empty($description) || empty($date) || empty($group_id) || empty($budget_id) || empty($income_id)) {
    echo json_encode(["status" => "error", "message" => "Please fill all the fields"]);
    exit();
}

// Check if the budget exists for group expenses (if budget_id is set)
$budget_check_sql = "SELECT * FROM BUDGET WHERE budgetID = ? AND groupID = ?";
$stmt = $conn->prepare($budget_check_sql);
$stmt->bind_param("ii", $budget_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "No valid budget found for the specified group ID"]);
    exit();
}

// Check if the income exists for the group
$income_check_sql = "SELECT * FROM INCOME WHERE incomeID = ? AND budgetID = ?";
$stmt = $conn->prepare($income_check_sql);
$stmt->bind_param("ii", $income_id, $budget_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "No valid income found for the specified income ID"]);
    exit();
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
            echo json_encode(["status" => "error", "message" => "Error uploading receipt image"]);
            exit();
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG & GIF files are allowed"]);
        exit();
    }
}

// Insert expense data into the Expense table using prepared statements
$stmt = $conn->prepare("INSERT INTO EXPENSE (userID, categoryID, amount, description, date, groupID, budgetID, incomeID, receipt_image) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iissiiisi", $user_id, $category_id, $amount, $description, $date, $group_id, $budget_id, $income_id, $receipt_image);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Expense added successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt->error]);
}
$stmt->close();
?>
