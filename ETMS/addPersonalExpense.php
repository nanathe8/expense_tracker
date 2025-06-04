<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$user_id = $_SESSION['userID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $budget_id = $_POST['budget_id'];

    // Validate required fields
    if (empty($category_id) || empty($amount) || empty($description) || empty($date) || empty($budget_id)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    // Step 1: Check the budget belongs to this user and is a personal budget (groupID IS NULL)
    $budget_check_sql = "SELECT * FROM BUDGET WHERE budgetID = ? AND userID = ? AND groupID IS NULL";
    $stmt = $conn->prepare($budget_check_sql);
    $stmt->bind_param("ii", $budget_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "No valid personal budget found for the specified budget ID"]);
        exit();
    }

    // Step 2: Set incomeID as NULL for personal expense
    $income_id = null;

    // Step 3: Handle file upload (receipt image)
    $receipt_image = '';
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["receipt_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

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

    // Step 4: Insert into EXPENSE table (incomeID as NULL)
    $stmt3 = $conn->prepare("INSERT INTO EXPENSE (categoryID, amount, description, date, budgetID, incomeID, receiptImage)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt3) {
        echo json_encode(["status" => "error", "message" => "Error preparing query: " . $conn->error]);
        exit();
    }

    $stmt3->bind_param("idsssis", $category_id, $amount, $description, $date, $budget_id, $income_id, $receipt_image);

    if ($stmt3->execute()) {
        echo json_encode(["status" => "success", "message" => "Personal expense inserted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt3->error]);
    }

    $stmt3->close();
    $conn->close();
}
?>
