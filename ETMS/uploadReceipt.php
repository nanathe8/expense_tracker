<?php
session_start();
include 'db.php'; // your db connection

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "error", "message" => "User is not logged in"]);
    exit();
}

$userID = $_SESSION['userID']; // Get the logged-in user ID


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['receipt_image']) && isset($_POST['shareID'])) {
    $shareID = intval($_POST['shareID']);
    $receipt_image = '';

    $target_dir = "uploads/";
    $filename = basename($_FILES["receipt_image"]["name"]);
    $target_file = $target_dir . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed)) {
        echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG & GIF files are allowed."]);
        exit();
    }

    if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
        $receipt_image = $filename;

        // Update DB with receipt filename for the shareID
        $stmt = $pdo->prepare("UPDATE debts SET receipt_image = ? WHERE shareID = ?");
        if ($stmt->execute([$receipt_image, $shareID])) {
            echo json_encode(["status" => "success", "message" => "Receipt uploaded successfully", "receipt_image" => $receipt_image]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update database"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error uploading receipt image."]);
    }
}
?>
