<?php
session_start();
include 'db_connection.php'; // make sure this has $conn

// Fetch userID from session (assuming user is logged in)
$userID = $_SESSION['userID'] ?? null;

// Get groupID and budgetID from GET parameters or session
$groupID = $_GET['groupID'] ?? null;
$budgetID = $_GET['budgetID'] ?? null;

if (!$userID || !$groupID || !$budgetID) {
    echo "Missing data.";
    exit;
}

// Check user role in the group
$sqlRole = "SELECT role FROM USER_GROUP WHERE userID = ? AND groupID = ?";
$stmtRole = $conn->prepare($sqlRole);
$stmtRole->bind_param("ii", $userID, $groupID);
$stmtRole->execute();
$resultRole = $stmtRole->get_result();

if ($resultRole->num_rows === 0) {
    echo "Access denied.";
    exit;
}

$userRole = $resultRole->fetch_assoc()['role'];
$isAdmin = ($userRole === 'admin');

// Fetch budget details
$sqlBudget = "SELECT * FROM BUDGET WHERE budgetID = ? AND groupID = ?";
$stmtBudget = $conn->prepare($sqlBudget);
$stmtBudget->bind_param("ii", $budgetID, $groupID);
$stmtBudget->execute();
$resultBudget = $stmtBudget->get_result();

if ($resultBudget->num_rows === 0) {
    echo "Budget not found.";
    exit;
}

$budget = $resultBudget->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Budget</title>
</head>
<body>
    <h2>Budget Details</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($budget['budgetName']) ?></p>
    <p><strong>Amount:</strong> RM <?= number_format($budget['budgetAmount'], 2) ?></p>
    <p><strong>Start Date:</strong> <?= $budget['startDate'] ?></p>
    <p><strong>End Date:</strong> <?= $budget['endDate'] ?></p>
    <p><strong>Total Income:</strong> RM <?= number_format($budget['totalIncome'], 2) ?></p>
    <p><strong>Total Expenses:</strong> RM <?= number_format($budget['totalExpenses'], 2) ?></p>
    <p><strong>Balance:</strong> RM <?= number_format($budget['balance'], 2) ?></p>

    <?php if ($isAdmin): ?>
        <a href="edit_budget.php?budgetID=<?= $budgetID ?>&groupID=<?= $groupID ?>">Edit</a> |
        <a href="delete_budget.php?budgetID=<?= $budgetID ?>&groupID=<?= $groupID ?>"
           onclick="return confirm('Are you sure you want to delete this budget?');">Delete</a>
    <?php else: ?>
        <p><em>You have view-only access.</em></p>
    <?php endif; ?>
</body>
</html>
