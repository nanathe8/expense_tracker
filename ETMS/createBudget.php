<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failure', 'message' => 'User is not logged in.']);
    exit();
}

$userID = $_SESSION['userID'];
$inputData = json_decode(file_get_contents('php://input'), true);

$budgetName = $inputData['budgetName'] ?? '';
$startDate = $inputData['startDate'] ?? '';
$endDate = $inputData['endDate'] ?? '';
$groupID = isset($inputData['groupID']) ? $inputData['groupID'] : NULL;

if (empty($budgetName) || empty($startDate) || empty($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'All fields are required.']);
    exit();
}

if (strtotime($startDate) > strtotime($endDate)) {
    echo json_encode(['status' => 'failure', 'message' => 'Start date cannot be later than end date.']);
    exit();
}

try {
    $stmt_insert = $conn->prepare(
        "INSERT INTO budget (budgetName, startDate, endDate, totalIncome, totalExpenses, balance, userID, groupID)
         VALUES (?, ?, ?, 0, 0, 0, ?, ?)"
    );
    $stmt_insert->bind_param("sssii", $budgetName, $startDate, $endDate, $userID, $groupID);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Budget created successfully.']);
    } else {
        throw new Exception($stmt_insert->error);
    }

    $stmt_insert->close();
} catch (Exception $e) {
    // Capture trigger errors
    $errorMessage = $e->getMessage();

    // Optional: Customize messages if needed
    if (strpos($errorMessage, 'trigger') !== false || strpos($errorMessage, 'already exists') !== false) {
        echo json_encode(['status' => 'failure', 'message' => 'Trigger error: ' . $errorMessage]);
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Error: ' . $errorMessage]);
    }
}

$conn->close();
?>
