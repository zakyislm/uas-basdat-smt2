<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if ($lang === 'id' || $lang === 'en') {
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + 31536000, '/');
    }
} elseif (isset($_COOKIE['lang'])) {
    $_SESSION['lang'] = $_COOKIE['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id'; 
}


function __($en, $id) {
    $current = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'id';
    return $current === 'id' ? $id : $en;
}

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
    die(__("Database Connection Failed: ", "Koneksi Database Gagal: ") . $conn->connect_error);
}
$conn->query("DELETE FROM users WHERE is_verified = 0 AND created_at < NOW() - INTERVAL 3 DAY");
function log_action($conn, $user_id, $action_detail) {
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action_detail) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action_detail);
    $stmt->execute();
}
function is_booking_dp_verified($conn, $trx_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM logs WHERE action_detail LIKE ?");
    $log_search = "%transaction ID " . intval($trx_id) . " status to: pending, payment: paid%";
    $stmt->bind_param("s", $log_search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['count'] > 0;
}


$expired_bookings = $conn->query("
    SELECT t.id, t.user_id, t.motorcycle_id, t.quantity, u.username, m.make, m.model 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN motorcycles m ON t.motorcycle_id = m.id
    WHERE t.type = 'booking' 
      AND t.payment_status = 'paid' 
      AND t.status = 'pending' 
      AND t.transaction_date < NOW() - INTERVAL 6 MONTH
");

if ($expired_bookings && $expired_bookings->num_rows > 0) {
    while ($trx = $expired_bookings->fetch_assoc()) {
        $trx_id = $trx['id'];
        $user_id = $trx['user_id'];
        $motor_id = $trx['motorcycle_id'];
        $qty = $trx['quantity'];
        $motor_name = $trx['make'] . ' ' . $trx['model'];
        
        
        $conn->query("UPDATE transactions SET status = 'cancelled', payment_status = 'refunded' WHERE id = $trx_id");
        
        
        $conn->query("UPDATE motorcycles SET stock = stock + $qty WHERE id = $motor_id");
        
        
        log_action($conn, $user_id, "System cancelled booking transaction ID $trx_id for exceeding 6-month deadline. DP refunded. || Sistem membatalkan transaksi booking ID $trx_id karena melebihi tenggat waktu 6 bulan. Uang DP direfund.");
        
        
        $notif_msg = "Your booking for $motor_name (ID #TX-$trx_id) has been cancelled because it was not settled within 6 months. Your DP has been refunded. || Booking Anda untuk $motor_name (ID #TX-$trx_id) telah dibatalkan karena tidak dilunasi dalam 6 bulan. Uang DP telah direfund.";
        $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($user_id, '$notif_msg', 'history', 'cancel', 'text-red-500', 'bg-red-100')");
    }
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
$wishlist_count = 0;
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
    
    $w_stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlists WHERE user_id = ?");
    if ($w_stmt) {
        $w_stmt->bind_param("i", $_SESSION['user_id']);
        $w_stmt->execute();
        $w_res = $w_stmt->get_result();
        if ($w_row = $w_res->fetch_assoc()) {
            $wishlist_count = (int)$w_row['total'];
        }
    }
}
?>
