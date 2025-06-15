<?php
include 'db.php';

// Drop trigger if exists
$dropTriggerSql = "DROP TRIGGER IF EXISTS before_budget_insert";
if (!$conn->query($dropTriggerSql)) {
    echo "Error dropping trigger: " . $conn->error;
    $conn->close();
    exit;
}

// Create trigger
$createTriggerSql = "
CREATE TRIGGER before_budget_insert
BEFORE INSERT ON BUDGET
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT 1 FROM BUDGET
    WHERE
      (userID = NEW.userID OR groupID = NEW.groupID)
      AND CURDATE() BETWEEN startDate AND endDate
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Active budget already exists. Cannot add new budget.';
  END IF;
END
";

if ($conn->query($createTriggerSql) === TRUE) {
    echo "Trigger created successfully";
} else {
    echo "Error creating trigger: " . $conn->error;
}

$conn->close();
?>
