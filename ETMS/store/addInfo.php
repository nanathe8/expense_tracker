<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');  
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_expense'])) {
        // Add Expense
        $category_id = $_POST['category_id'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date = $_POST['date'];

        // Handle file upload for receipt
        $receipt_image = '';
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
            $target_dir = "image/";
            $target_file = $target_dir . basename($_FILES["receipt_image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is a valid image type (jpg, jpeg, png, gif)
            if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
                    $receipt_image = $target_file;  // Save the path to the image
                } else {
                    echo "Error uploading receipt image.";
                }
            } else {
                echo "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }

        // Insert expense data into the Expense table
        $sql_expense = "INSERT INTO Expense (userID, categoryID, amount, description, date, receiptmage)
                        VALUES ('$user_id', '$category_id', '$amount', '$description', '$date', '$receipt_image')";
        if ($conn->query($sql_expense)) {
            echo "<script>alert('Expense added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding expense: " . $conn->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Info - Expenses Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <style>
        /* General Styling */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Jost', sans-serif;
            background: linear-gradient(to bottom, #0f0c29, #302b63, #24243e);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #2f2f2f;
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-radius: 10px;
            box-shadow: 5px 0px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .sidebar img {
            width: 80%;
            height: auto;
            display: block;
            margin: 20px auto;
            max-height: 60px;
        }

        .sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #6d44b8;
        }

        .active {
            background-color: #573b8a;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
            text-align: center;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
            width: 400px;
            backdrop-filter: blur(10px);
            margin: 0 auto;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .form-container label {
            font-size: 1.1em;
            margin-bottom: 5px;
            color: white;
            display: block;
            text-align: left;
        }

        .form-container input, .form-container select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1em;
            transition: background 0.3s;
        }

        .form-container input:focus, .form-container select:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #6d44b8;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .form-container button:hover {
            background-color: #573b8a;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <img src="logo.png" alt="App Logo"> <!-- Replace this with your actual logo -->
        <a href="mainDashboard.php">Home</a>
        <a href="statistic.php">Statistics</a>
        <a href="addInfo.php" class="active">Add Info</a>
        <a href="#">Settings</a>
    </div>

    <!-- Main Content Section -->
    <div class="main-content">
        <h1>Add Information</h1>

        <div class="form-container">
            <h2>Add Expense</h2>
            <form method="POST" action="addInfo.php" enctype="multipart/form-data">
                <label for="category_id">Category:</label>
                <select name="category_id" required>
                    <?php
                    $sql_categories = "SELECT * FROM Category";
                    $result_categories = $conn->query($sql_categories);
                    while ($row = $result_categories->fetch_assoc()) {
                        echo "<option value='" . $row['categoryID'] . "'>" . $row['categoryName'] . "</option>";
                    }
                    ?>
                </select>

                <label for="amount">Amount (RM):</label>
                <input type="number" name="amount" required>

                <label for="description">Description:</label>
                <input type="text" name="description" required>

                <label for="date">Date:</label>
                <input type="date" name="date" required>

                <label for="receipt_image">Upload Receipt (optional):</label>
                <input type="file" name="receipt_image" accept="image/*">

                <button type="submit" name="add_expense">Add Expense</button>
            </form>
        </div>
    </div>

</body>
</html>
