<?php
session_start();  // Start the session at the very beginning

include 'db.php';  // Include database connection

// Debugging: Check the session data
error_log("Session ID: " . session_id());  // Log the session ID
error_log("Session data: " . print_r($_SESSION, true));  // Log the session data

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$user_id = $_SESSION['userID']; // Get the logged-in user ID

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Read raw POST data (JSON)
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Check if the required data is present in the decoded JSON
    if (!isset($inputData['amount']) || !isset($inputData['source']) || !isset($inputData['date'])) {
        echo json_encode(["status" => "error", "message" => "Please fill all the fields"]);
        exit();
    }

    // Get form inputs from decoded JSON data
    $amount = $inputData['amount'];
    $source = $inputData['source']; // Get income source (e.g., Salary, Freelance, etc.)
    $date = $inputData['date'];

    // Validate inputs
    if (empty($amount) || empty($source) || empty($date)) {
        echo json_encode(["status" => "error", "message" => "Please fill all the fields"]);
        exit();
    }

    // Make sure amount is a valid number
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Amount must be a positive number"]);
        exit();
    }

    // Ensure the date format is correct (YYYY-MM-DD)
    if (!strtotime($date)) {
        echo json_encode(["status" => "error", "message" => "Invalid date format"]);
        exit();
    }

    // Query to fetch personal budgets (groupID is NULL) for the given userID
    $query = "SELECT budgetID FROM BUDGET WHERE userID = ? AND groupID IS NULL ORDER BY startDate DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);  // Bind the user ID for the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $budgetID = $row['budgetID'];  // Get the selected budgetID for the user
    } else {
        echo json_encode(["status" => "error", "message" => "No budget found for this user."]);
        exit();
    }

    // Insert data into the INCOME table using prepared statement
    $sql = "INSERT INTO INCOME (amount, source, date, budgetID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dssi", $amount, $source, $date, $budgetID); // 'dssi' matches types: decimal, string, string, integer

    if ($stmt->execute()) {
        // Step 1: Get the current total income for the selected budget
        $sql = "SELECT totalIncome FROM BUDGET WHERE budgetID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $budgetID); // Use the selected budgetID to get the correct budget
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentTotalIncome = $row['totalIncome'];  // Current total income for the budget

            // Step 2: Add the new income amount to the current total income
            $updatedTotalIncome = $currentTotalIncome + $amount;

            // Step 3: Update the totalIncome in the BUDGET table
            $sqlUpdate = "UPDATE BUDGET SET totalIncome = ? WHERE budgetID = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("di", $updatedTotalIncome, $budgetID);  // 'd' for decimal, 'i' for integer
            if ($stmtUpdate->execute()) {
                echo json_encode(["status" => "success", "message" => "Income added successfully and total income updated!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error updating total income: " . $conn->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Budget not found or total income retrieval failed."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding income: " . $conn->error]);
    }
    
    
}
?>
