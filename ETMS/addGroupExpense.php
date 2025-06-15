<?php
include 'db.php'; // Include your database connection
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$userID = $_SESSION['userID']; // Get the logged-in user ID

// If no POST data for expense, return groups for the user
if (empty($_POST['category_id'])) {
    $stmt = $conn->prepare("SELECT eg.groupID, eg.groupName 
                            FROM EXPENSE_GROUP eg 
                            JOIN USER_GROUP ug ON eg.groupID = ug.groupID 
                            WHERE ug.userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }

    echo json_encode(['status' => 'success', 'groups' => $groups]);
    exit();
}

// Handle expense submission
$category_id = $_POST['category_id'] ?? ''; // Category ID
$amount = $_POST['amount'] ?? ''; // Amount
$description = $_POST['description'] ?? ''; // Description
$date = $_POST['date'] ?? ''; // Date
$group_id = $_POST['group_id'] ?? ''; // Group ID
$income_id = $_POST['income_id'] ?? null; // Income ID (can be null)

// Log the received POST data for debugging
error_log("Received POST data: " . print_r($_POST, true));

// Validate required fields
if (empty($category_id) || empty($amount) || empty($description) || empty($date) || empty($group_id)) {
    echo json_encode(["status" => "error", "message" => "Please fill all the required fields"]);
    exit();
}

// Fetch the `budget_id` for the selected `group_id` automatically
$budget_id = null; // Initialize budget_id as null
$stmt = $conn->prepare("SELECT budgetID FROM BUDGET WHERE groupID = ? LIMIT 1");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

// If the group has an associated budget, fetch the budget_id
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $budget_id = $row['budgetID'];
} else {
    echo json_encode(["status" => "error", "message" => "No budget found for the specified group"]);
    exit();
}

   // Step 3: Handle file upload (receipt image)
    $receipt_image = '';
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
        $target_dir = "uploads/";
        $filename = basename($_FILES["receipt_image"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
                $receipt_image = $filename; // Only the filename is saved to DB
            } else {
                echo json_encode(["status" => "error", "message" => "Error uploading receipt image."]);
                exit();
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG & GIF files are allowed."]);
            exit();
        }
    }

// Log the final data before insertion
error_log("Inserting expense with parameters: 
    Category ID: $category_id, Amount: $amount, Description: $description, 
    Date: $date, Group ID: $group_id, Budget ID: $budget_id, Income ID: $income_id, 
    Receipt Image: $receipt_image");

// Insert into the EXPENSE table
$stmt3 = $conn->prepare("INSERT INTO EXPENSE (categoryID, amount, description, date, budgetID, incomeID, receiptImage, paidBy)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt3) {
    echo json_encode(["status" => "error", "message" => "Error preparing query: " . $conn->error]);
    exit();
}

$stmt3->bind_param("idsssisi", $category_id, $amount, $description, $date, $budget_id, $income_id, $receipt_image, $userID);

// Execute the query and check if it was successful
if ($stmt3->execute()) {
    echo json_encode(["status" => "success", "message" => "Group expense added successfully!"]);
} else {
    // Log any errors if the query execution fails
    echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt3->error]);
}

if ($stmt3->execute()) {
    $expenseID = $conn->insert_id; // Get the newly inserted expense ID

    // Fetch all users in the group
    $stmt_users = $conn->prepare("SELECT userID FROM USER_GROUP WHERE groupID = ?");
    $stmt_users->bind_param("i", $group_id);
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();

    $userIDs = [];
    while ($row = $result_users->fetch_assoc()) {
        $userIDs[] = $row['userID'];
    }

    // Calculate share amount per user
    $split_amount = round($amount / count($userIDs), 2); // Round to 2 decimal places

    // Insert into EXPENSE_SHARE table
    $stmt_share = $conn->prepare("INSERT INTO EXPENSE_SHARE (expenseID, userID, amount) VALUES (?, ?, ?)");

    foreach ($userIDs as $uid) {
        $stmt_share->bind_param("iid", $expenseID, $uid, $split_amount);
        $stmt_share->execute();
    }

    $stmt_share->close();
    $stmt_users->close();

    echo json_encode(["status" => "success", "message" => "Group expense added and shared successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt3->error]);
}

// Close the statement and connection
$stmt3->close();
$conn->close();
?>
