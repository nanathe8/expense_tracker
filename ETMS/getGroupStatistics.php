<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(["status" => "failure", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];
$month = isset($_GET['month']) ? $_GET['month'] : null;

// SQL to get expenses grouped by category and date for groups the user belongs to
$sql = "
    SELECT 
        g.groupName,
        c.categoryName AS category,
        SUM(e.amount) AS amount,
        e.date
    FROM EXPENSE e
    JOIN CATEGORY c ON e.categoryID = c.categoryID
    JOIN BUDGET b ON e.budgetID = b.budgetID
    JOIN EXPENSE_GROUP g ON b.groupID = g.groupID
    JOIN USER_GROUP ug ON g.groupID = ug.groupID
    WHERE ug.userID = ?
";

// Add month filter if valid
if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $sql .= " AND DATE_FORMAT(e.date, '%Y-%m') = ?";
}

$sql .= "
    GROUP BY g.groupName, c.categoryName, e.date
    ORDER BY e.date DESC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['status' => 'failure', 'message' => $conn->error]);
    exit();
}

if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $stmt->bind_param("is", $user_id, $month);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $rows
]);

$stmt->close();
$conn->close();
?>
