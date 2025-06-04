<?php
session_start();  // Start the session to access session variables
include 'db.php';  // Include database connection

// Check if the user is logged in by checking the session
if (!isset($_SESSION['userID'])) {  // Use 'userID' instead of 'user_id'
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$user_id = $_SESSION['userID'];  // Get the logged-in user ID

// Step 1: Get the groupID from the BUDGET table where the user belongs
$sql = "
    SELECT b.groupID
    FROM BUDGET b
    JOIN USER_GROUP ug ON ug.groupID = b.groupID
    WHERE b.userID = ? AND ug.userID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if a group is found for this user
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $groupID = $row['groupID'];
    
    // Step 2: Fetch the group name from EXPENSE_GROUP table using the groupID
    $groupSql = "SELECT groupName FROM EXPENSE_GROUP WHERE groupID = ?";
    $groupStmt = $conn->prepare($groupSql);
    $groupStmt->bind_param("i", $groupID);
    $groupStmt->execute();
    $groupResult = $groupStmt->get_result();
    
    // Check if the group name is found
    if ($groupResult->num_rows > 0) {
        $groupData = $groupResult->fetch_assoc();
        $groupName = $groupData['groupName'];
        
        // Step 3: Fetch the role from USER_GROUP table
        $roleSql = "SELECT role FROM USER_GROUP WHERE groupID = ? AND userID = ?";
        $roleStmt = $conn->prepare($roleSql);
        $roleStmt->bind_param("ii", $groupID, $user_id);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        
        // Check if the role is found for this user
        if ($roleResult->num_rows > 0) {
            $roleData = $roleResult->fetch_assoc();
            $role = $roleData['role'];
            
            // Return the group name and user role
            echo json_encode([
                "status" => "success",
                "message" => "User group and role fetched successfully",
                "groupName" => $groupName,
                "role" => $role
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "No role found for the user in the group"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No group found for the user"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User is not part of any group"]);
}

$stmt->close();
$groupStmt->close();
$roleStmt->close();
$conn->close();
?>
