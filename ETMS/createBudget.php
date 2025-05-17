<?php
session_start();  // Start the session
error_log("Session ID: " . session_id());  // Log session ID to debug


// Check if the user is logged in by verifying if userID is in the session
if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];  // Assuming the userID is stored in the session
$groupID = isset($_POST['groupID']) ? $_POST['groupID'] : NULL;  // Get groupID from POST or set it as NULL if not provided

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from the request
    $budgetAmount = $_POST['budgetAmount'];
    $startDate = $_POST['dateStart'];
    $endDate = $_POST['dateEnd'];
    $totalIncome = isset($_POST['totalIncome']) ? $_POST['totalIncome'] : NULL;  // Optional field for totalIncome
    $totalExpenses = isset($_POST['totalExpenses']) ? $_POST['totalExpenses'] : NULL;  // Optional field for totalExpenses

    // Basic validation
    if (empty($budgetAmount) || empty($startDate) || empty($endDate) || empty($groupID)) {
        echo json_encode(['status' => 'failure', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate budgetAmount to be a number
    if (!is_numeric($budgetAmount) || $budgetAmount <= 0) {
        echo json_encode(['status' => 'failure', 'message' => 'Invalid budget amount. Please enter a valid number greater than zero.']);
        exit;
    }

    // Validate totalIncome and totalExpenses to be numbers, if provided
    if ($totalIncome !== NULL && !is_numeric($totalIncome)) {
        echo json_encode(['status' => 'failure', 'message' => 'Total income must be a valid number.']);
        exit;
    }

    if ($totalExpenses !== NULL && !is_numeric($totalExpenses)) {
        echo json_encode(['status' => 'failure', 'message' => 'Total expenses must be a valid number.']);
        exit;
    }

    // Check if startDate is before endDate
    if (strtotime($startDate) > strtotime($endDate)) {
        echo json_encode(['status' => 'failure', 'message' => 'Start date cannot be later than end date.']);
        exit;
    }

    // Insert budget data into the database
    $stmt_insert = $conn->prepare("INSERT INTO budget (budgetAmount, startDate, endDate, totalIncome, totalExpenses, userID, groupID) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("dssssii", $budgetAmount, $startDate, $endDate, $totalIncome, $totalExpenses, $userID, $groupID);

    // Execute the query and check for success
    if ($stmt_insert->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Budget created successfully.']);
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Budget creation failed. Please try again later.']);
    }

    // Close the prepared statement and connection
    $stmt_insert->close();
    $conn->close();
} else {
    // If not POST request, return failure message
    echo json_encode(['status' => 'failure', 'message' => 'Invalid request method.']);
    exit;
}
?>
