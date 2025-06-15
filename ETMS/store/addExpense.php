<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['userID'];  // Get the user ID from the session

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $group_id = $_POST['group_id'];  // If it's a shared expense, this is not null
    $budget_id = $_POST['budget_id'];  // The selected budget ID
    $income_id = $_POST['income_id'];  // The selected income ID (personal or group)

    // Validate required fields
    if (empty($category_id) || empty($amount) || empty($description) || empty($date)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    // Check if the budget exists and is not soft-deleted
    if ($budget_id) {
        $budget_check_sql = "SELECT * FROM BUDGET WHERE budgetID = ? AND (userID = ? OR groupID = ?)";
        $stmt = $conn->prepare($budget_check_sql);
        $stmt->bind_param("iii", $budget_id, $user_id, $group_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "No budget found with the given ID."]);
            exit();
        }

        $budget_data = $result->fetch_assoc();

        // Check if the budget has been soft-deleted
        if (!is_null($budget_data['deleted_at'])) {
            echo json_encode(["status" => "error", "message" => "This budget has been deleted. Please create a new budget to add expenses."]);
            exit();
        }

        $user_id_from_budget = $budget_data['userID'];
        $group_id_from_budget = $budget_data['groupID'];
    }

    // Handle file upload for receipt
    $receipt_image = '';
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        $target_dir = "uploads/";  // Make sure the uploads folder exists
        $target_file = $target_dir . basename($_FILES["receipt_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is a valid image type
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
                $receipt_image = $target_file;
            } else {
                echo json_encode(["status" => "error", "message" => "Error uploading receipt image."]);
                exit();
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG & GIF files are allowed."]);
            exit();
        }
    }

    // Insert expense data into the Expense table using prepared statements
    $stmt = $conn->prepare("INSERT INTO EXPENSE (userID, categoryID, amount, description, date, groupID, budgetID, incomeID, receiptImage) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissiiiss", $user_id_from_budget, $category_id, $amount, $description, $date, $group_id_from_budget, $budget_id, $income_id, $receipt_image);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Expense added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt->error]);
    }

    $stmt->close();
}
?>
