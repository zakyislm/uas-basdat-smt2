<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'dealer_db';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

function log_action($conn, $user_id, $action_detail) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action_detail) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action_detail);
    $stmt->execute();
}
?>
