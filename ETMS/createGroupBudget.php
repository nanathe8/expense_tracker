<?php
include 'db.php';

// Enable detailed MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set response type
header('Content-Type: application/json');

// Decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON input: ' . json_last_error_msg()
    ]);
    exit;
}

// Start session to access userID
session_start();

// If no budgetName provided, return groups for the logged-in user
if (!isset($input['budgetName'])) {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
        exit;
    }

    $userID = $_SESSION['userID'];

    $stmt = $conn->prepare("
        SELECT eg.groupID, eg.groupName
        FROM EXPENSE_GROUP eg
        JOIN USER_GROUP ug ON eg.groupID = ug.groupID
        WHERE ug.userID = ?
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = ['group' => $row];
    }

    echo json_encode([
        'status' => 'success',
        'groups' => $groups
    ]);
    exit;
}

// Extract input fields
$budgetName = $input['budgetName'] ?? '';
$budgetAmount = $input['budgetAmount'] ?? '';
$startDate = $input['startDate'] ?? '';
$endDate = $input['endDate'] ?? '';
$groupID = $input['groupID'] ?? null;
$userID = $input['userID'] ?? null;

// Validate required fields
if (
    !$budgetName || !$budgetAmount || !$startDate || !$endDate ||
    (empty($groupID) && empty($userID))
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields or no group/user specified']);
    exit;
}

// Format amount and IDs
$budgetAmount = floatval($budgetAmount);
$groupID = !empty($groupID) ? (int)$groupID : null;
$userID = !empty($userID) ? (int)$userID : null;

try {
    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO BUDGET (budgetName, startDate, endDate, budgetAmount, totalIncome, totalExpenses, balance, groupID, userID)
        VALUES (?, ?, ?, ?, 0, 0, 0, ?, ?)
    ");

    $stmt->bind_param("sssdii", $budgetName, $startDate, $endDate, $budgetAmount, $groupID, $userID);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Budget created']);
} catch (mysqli_sql_exception $e) {
    $error = $e->getMessage();

    // Handle custom trigger messages exactly as defined in the TRIGGER
    if (strpos($error, 'Group already has an active budget') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Group already has an active budget. Wait until the current one ends.']);
    } elseif (strpos($error, 'User already has an active budget') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'User already has an active budget. Wait until the current one ends.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $error]);
    }
}
?>
