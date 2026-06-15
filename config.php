<?php
session_start();

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    $host = isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost';
    $user = isset($env['DB_USER']) ? $env['DB_USER'] : 'root';
    $password = isset($env['DB_PASS']) ? $env['DB_PASS'] : '';
    $dbname = isset($env['DB_NAME']) ? $env['DB_NAME'] : 'dealer_db';
} else {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $dbname = 'dealer_db';
}

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

function log_action($conn, $user_id, $action_detail) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action_detail) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action_detail);
    $stmt->execute();
}

$notifications = [];
$unread_notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    $n_sql = "SELECT * FROM notifications WHERE user_id = ? OR target_role = ? ORDER BY created_at DESC";
    $n_stmt = $conn->prepare($n_sql);
    $n_stmt->bind_param("is", $uid, $role);
    $n_stmt->execute();
    $n_res = $n_stmt->get_result();
    
    while ($row = $n_res->fetch_assoc()) {
        $notifications[] = $row;
        if (!$row['is_read']) {
            $unread_notif_count++;
        }
    }
}
$notif_count = $unread_notif_count;

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'y',
        'm' => 'm',
        'w' => 'w',
        'd' => 'd',
        'h' => 'h',
        'i' => 'm',
        's' => 's',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $c_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM carts WHERE user_id = ?");
    if ($c_stmt) {
        $c_stmt->bind_param("i", $_SESSION['user_id']);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $cart_count = (int)$c_row['total'];
        }
    }
}
?>
