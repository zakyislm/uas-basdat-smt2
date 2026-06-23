<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    
    if (empty($query)) {
        echo json_encode(["status" => "error", "message" => "Query cannot be empty"]);
        exit;
    }
    
    
    $clean_query = preg_replace('/--.*/', '', $query);
    $clean_query = preg_replace('/\/\*.*?\*\//s', '', $clean_query);
    $clean_query = trim($clean_query);
    
    
    if (!preg_match('/^(select|insert|update)\b/i', $clean_query)) {
        echo json_encode(["status" => "error", "message" => "Only SELECT, INSERT, or UPDATE queries are allowed."]);
        exit;
    }
    
    
    if (preg_match('/\b(alter|drop|delete|truncate|grant|revoke)\b/i', $clean_query)) {
        echo json_encode(["status" => "error", "message" => "Query contains forbidden keywords (ALTER, DROP, DELETE, etc.)."]);
        exit;
    }
    
    
    try {
        $result = $conn->query($query);
        if ($result === false) {
            echo json_encode(["status" => "error", "message" => $conn->error]);
            exit;
        }
        
        if ($result === true) {
            
            $affected_rows = $conn->affected_rows;
            $msg = "Query executed successfully. Affected rows: $affected_rows";
            if (preg_match('/^insert\b/i', $clean_query)) {
                $msg .= ". Last insert ID: " . $conn->insert_id;
            }
            echo json_encode(["status" => "success", "message" => $msg]);
        } else {
            
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
            echo json_encode([
                "status" => "success",
                "message" => "Query executed successfully. Retrieved " . count($rows) . " rows.",
                "data" => $rows
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
