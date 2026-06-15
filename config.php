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

// --- NOTIFICATIONS LOGIC ---
$notifications = [];
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner') {
        // Admin/Owner: Pending transactions
        $n_stmt = $conn->query("SELECT id FROM transactions WHERE payment_status = 'pending_verification'");
        if ($n_stmt) {
            while ($n = $n_stmt->fetch_assoc()) {
                $notifications[] = [
                    'icon' => 'payments',
                    'message' => "Pending verification: #TX-" . $n['id'],
                    'link' => 'admin.php?page=transactions',
                    'color' => 'text-amber-500',
                    'bg' => 'bg-amber-100'
                ];
            }
        }
        // Admin/Owner: Unverified users
        $u_stmt = $conn->query("SELECT id, username FROM users WHERE is_verified = 0");
        if ($u_stmt) {
            while ($u = $u_stmt->fetch_assoc()) {
                $notifications[] = [
                    'icon' => 'person_add',
                    'message' => "Unverified user: " . htmlspecialchars($u['username']),
                    'link' => 'admin.php?page=users',
                    'color' => 'text-blue-500',
                    'bg' => 'bg-blue-100'
                ];
            }
        }
    } else {
        // User: General notification
        $notifications[] = [
            'icon' => 'campaign',
            'message' => "Selamat datang di MotoTrack Pro! Temukan motor impian Anda.",
            'link' => 'discover.php',
            'color' => 'text-secondary',
            'bg' => 'bg-red-100'
        ];
        
        // User: Unpaid orders
        $p_stmt = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND payment_status = 'unpaid' AND status != 'cancelled'");
        $p_stmt->bind_param("i", $_SESSION['user_id']);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        while ($p = $p_res->fetch_assoc()) {
            $notifications[] = [
                'icon' => 'warning',
                'message' => "Menunggu pembayaran: #TX-" . $p['id'],
                'link' => 'payment.php?id=' . $p['id'],
                'color' => 'text-orange-500',
                'bg' => 'bg-orange-100'
            ];
        }
    }
}
$notif_count = count($notifications);

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
