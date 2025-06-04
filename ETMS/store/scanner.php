<?php
// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['receipt_image'])) {
    $file = $_FILES['receipt_image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    // Check for errors during file upload
    if ($file_error === 0) {
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png');
        
        // Check if the file is an image
        if (in_array($file_ext, $allowed)) {
            // Set the upload path (uploads/receipt_123.jpg)
            $upload_path = 'uploads/' . uniqid('receipt_', true) . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_path);

            // Save the image path and send the file name to OCR processing
            echo "Receipt uploaded successfully!";
        } else {
            echo "Please upload a valid image (JPG, JPEG, PNG).";
        }
    } else {
        echo "Error uploading the file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Receipt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="scanner-container">
        <h2>Upload Your Receipt</h2>
        <form action="scanner.php" method="POST" enctype="multipart/form-data" onsubmit="processReceipt()">
            <input type="file" name="receipt_image" accept="image/*" required>
            <button type="submit" class="btn">Upload Receipt</button>
        </form>
        <textarea id="extracted-text" readonly></textarea>
    </div>

    <!-- Add Tesseract.js for OCR -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.1/dist/tesseract.min.js"></script>
    <script>
        // JavaScript for processing the receipt image with OCR (Tesseract.js)
        function processReceipt() {
            var fileInput = document.querySelector('input[type="file"]');
            var file = fileInput.files[0];

            // Initialize Tesseract.js for OCR
            Tesseract.recognize(
                file,
                'eng', // OCR language (use 'eng' for English)
                {
                    logger: (m) => console.log(m), // Optional: show progress
                }
            ).then(({ data: { text } }) => {
                console.log("OCR Output: ", text);
                // Display extracted text
                document.getElementById("extracted-text").value = text;

                // Extract relevant data (amount, merchant, date) from OCR text
                var amount = extractAmount(text);
                var merchant = extractMerchant(text);
                var date = extractDate(text);

                // Save data to the database
                saveReceiptData(amount, merchant, date, file.name);
            });
        }

        // Extract amount from OCR text
        function extractAmount(text) {
            var regex = /\$([0-9,]+\.[0-9]{2})/;
            var match = text.match(regex);
            return match ? match[0] : 'N/A';
        }

        // Extract merchant name from OCR text
        function extractMerchant(text) {
            var lines = text.split('\n');
            return lines[0] || 'Unknown Merchant';
        }

        // Extract date from OCR text (example: MM/DD/YYYY format)
        function extractDate(text) {
            var regex = /\b(\d{2}\/\d{2}\/\d{4})\b/;
            var match = text.match(regex);
            return match ? match[0] : 'Unknown Date';
        }

        // Save receipt data (OCR result + image path) to the database
        function saveReceiptData(amount, merchant, date, fileName) {
            var formData = new FormData();
            formData.append("amount", amount);
            formData.append("merchant", merchant);
            formData.append("date", date);
            formData.append("receipt_image", fileName); // Image name stored in DB

            // Send data to a PHP file that saves it to the database
            fetch('save_receipt.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  console.log('Receipt data saved:', data);
              })
              .catch(error => {
                  console.error('Error saving receipt:', error);
              });
        }
    </script>
</body>
</html>
