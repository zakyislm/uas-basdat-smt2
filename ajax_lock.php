<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $flag_file = __DIR__ . '/unlock.flag';

    if ($action === 'lock_timer') {
        
        set_time_limit(120);

        try {
            $conn->query("LOCK TABLES motorcycles WRITE, transactions WRITE, users READ");
            
            
            sleep(10);
            
            $conn->query("UNLOCK TABLES");
            
            echo json_encode(["status" => "success", "message" => "Unlocked successfully"]);
        } catch (Exception $e) {
            $conn->query("UNLOCK TABLES");
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'unlock') {
        
        touch($flag_file);
        echo json_encode(["status" => "success", "message" => "Unlock flag set"]);
        exit;
    }
}
