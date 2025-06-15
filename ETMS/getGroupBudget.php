<?php
header('Content-Type: application/json');

include 'db.php'; // Make sure this file sets $conn to your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['budgetID'])) {
        $budgetID = intval($_POST['budgetID']);

        $sql = "
            SELECT g.groupID, g.groupName, g.groupDescription
            FROM EXPENSE_GROUP g
            JOIN BUDGET b ON g.groupID = b.groupID
            WHERE b.budgetID = ? AND b.deleted_at = NULL
            LIMIT 1
        ";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $budgetID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo json_encode(['group' => $row]);
            } else {
                echo json_encode(['group' => null, 'message' => 'No group found for this budgetID']);
            }

            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to prepare SQL statement']);
        }
    } else {
        echo json_encode(['error' => 'budgetID not provided']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
