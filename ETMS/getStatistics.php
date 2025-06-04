<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(["status" => "failure", "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];

// Get month filter from GET parameter, or null if not provided
$month = isset($_GET['month']) ? $_GET['month'] : null;

// Base SQL query (no trailing GROUP BY or ORDER BY here)
$sql = "
    SELECT 
        c.categoryName AS category,
        SUM(e.amount) AS amount,
        e.date
    FROM EXPENSE e
    JOIN CATEGORY c ON e.categoryID = c.categoryID
    JOIN BUDGET b ON e.budgetID = b.budgetID
    WHERE b.userID = ? AND b.groupID IS NULL
";

// Add month filter if valid format YYYY-MM
if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $sql .= " AND DATE_FORMAT(e.date, '%Y-%m') = ?";
}

// Add GROUP BY and ORDER BY only once at the end
$sql .= "
    GROUP BY c.categoryName, e.date
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
