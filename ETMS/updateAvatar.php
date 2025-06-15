<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userID = $_SESSION['userID'];

if (!isset($_FILES['avatar'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$uploadDir = 'uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Use fixed filename based on userID
$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$filename = $userID . '.' . $ext;
$filepath = $uploadDir . $filename;

if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
    // Optionally update DB if you want to track the file extension (optional)
    $stmt = $conn->prepare("UPDATE USERS SET avatar = ? WHERE userID = ?");
    $stmt->bind_param("si", $filename, $userID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'avatarPath' => $filepath]);
    } else {
        echo json_encode(['error' => 'Failed to update avatar in DB.']);
    }
} else {
    echo json_encode(['error' => 'Failed to move uploaded file.']);
}
?>
