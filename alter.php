<?php
require_once 'config.php';

$sql = "ALTER TABLE transactions MODIFY COLUMN payment_status ENUM('unpaid', 'pending_verification', 'paid', 'refunded') DEFAULT 'unpaid'";

if ($conn->query($sql) === TRUE) {
    echo "Table transactions altered successfully";
} else {
    echo "Error altering table: " . $conn->error;
}
$conn->close();
?>
