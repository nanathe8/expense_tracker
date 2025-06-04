<?php
// Include the database connection
include 'db.php';  // Make sure this is the correct path to your db.php file

session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];  // Retrieve userID from session

// Get data from POST request
$inputData = json_decode(file_get_contents('php://input'), true);

// Extract budget data
$budgetName = $inputData['budgetName'] ?? '';  // New budgetName field
$startDate = $inputData['startDate'] ?? '';  // Changed from 'startDate' to 'dateStart'
$endDate = $inputData['endDate'] ?? '';      // Changed from 'endDate' to 'dateEnd'
$groupID = isset($inputData['groupID']) ? $inputData['groupID'] : NULL;  // If no groupID is provided, set it as NULL

// Basic validation
if (empty($budgetName) || empty($startDate) || empty($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'All fields are required.']);
    exit();
}

// Check if dateStart is before dateEnd
if (strtotime($startDate) > strtotime($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'Start date cannot be later than end date.']);
    exit();
}

// Insert the budget data into the database (including the new budgetName)
$stmt_insert = $conn->prepare("INSERT INTO budget (budgetName, startDate, endDate, totalIncome, totalExpenses, balance, userID, groupID) 
VALUES (?, ?, ?, 0, 0, 0, ?, ?)");

// Now bind the parameters, ensuring the types match the query
$stmt_insert->bind_param("sssii", $budgetName, $startDate, $endDate, $userID, $groupID);

if ($stmt_insert->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Budget created successfully.']);
} else {
    echo json_encode(['status' => 'failure', 'message' => 'Failed to create budget.']);
}

$stmt_insert->close();
$conn->close();
?>
