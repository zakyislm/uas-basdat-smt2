<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Silakan masuk untuk bertransaksi.';
    header("Location: login.php");
    exit();
}

// Check if user is verified
if ($_SESSION['is_verified'] != 1) {
    $_SESSION['flash_message'] = 'Maaf, akun Anda belum diverifikasi. Hanya pengguna yang sudah diverifikasi yang dapat melakukan transaksi.';
    header("Location: index.php");
    exit();
}

$motor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch motorcycle details
$stmt = $conn->prepare("SELECT * FROM motorcycles WHERE id = ? AND stock > 0");
$stmt->bind_param("i", $motor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['flash_message'] = 'Maaf, motor ini tidak ditemukan atau stok sudah habis.';
    header("Location: index.php");
    exit();
}

$motorcycle = $result->fetch_assoc();
$error = '';

// Handle transaction submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $quantity = intval($_POST['quantity']);
    $type = $_POST['type']; // 'booking' or 'buy'
    
    if ($quantity <= 0) {
        $error = "Jumlah pesanan tidak valid.";
    } elseif ($quantity > $motorcycle['stock']) {
        $error = "Maaf, jumlah pesanan melebihi sisa stok yang tersedia (Sisa: " . $motorcycle['stock'] . ").";
    } elseif ($type !== 'booking' && $type !== 'buy') {
        $error = "Tipe transaksi tidak valid.";
    } else {
        // Insert transaction
        $t_stmt = $conn->prepare("INSERT INTO transactions (user_id, motorcycle_id, quantity, type) VALUES (?, ?, ?, ?)");
        $t_stmt->bind_param("iiis", $user_id, $motor_id, $quantity, $type);
        
        if ($t_stmt->execute()) {
            // Kurangi stok
            $update_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock - ? WHERE id = ?");
            $update_stmt->bind_param("ii", $quantity, $motor_id);
            $update_stmt->execute();
            
            $tipe_text = ($type === 'booking') ? "Booking" : "Pembelian";
            log_action($conn, $user_id, "Membuat transaksi $type sebanyak $quantity unit untuk motor ID $motor_id");

            $_SESSION['flash_message'] = "Transaksi $tipe_text berhasil untuk " . $quantity . " unit " . $motorcycle['make'] . " " . $motorcycle['model'] . "!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat menyimpan transaksi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi - Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav><a href="index.php">Kembali ke Beranda</a></nav>
    </header>
    <hr>

    <main>
        <h2>Transaksi Motor</h2>
        
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <p>Detail Kendaraan:</p>
        <ul>
            <li><strong>Merk:</strong> <?= htmlspecialchars($motorcycle['make']) ?></li>
            <li><strong>Model:</strong> <?= htmlspecialchars($motorcycle['model']) ?></li>
            <li><strong>Harga Satuan:</strong> Rp <?= number_format($motorcycle['price'], 0, ',', '.') ?></li>
            <li><strong>Sisa Stok:</strong> <?= $motorcycle['stock'] ?> unit</li>
        </ul>

        <form method="POST" action="checkout.php?id=<?= $motor_id ?>">
            <div>
                <label for="quantity">Jumlah Pesanan:</label><br>
                <input type="number" id="quantity" name="quantity" min="1" max="<?= $motorcycle['stock'] ?>" value="1" required>
            </div>
            <br>
            <div>
                <label for="type">Tipe Transaksi:</label><br>
                <select id="type" name="type" required>
                    <option value="buy">Beli Langsung</option>
                    <option value="booking">Booking / Reservasi</option>
                </select>
            </div>
            <br>
            <button type="submit">Konfirmasi Transaksi</button>
            <a href="index.php"><button type="button">Batal</button></a>
        </form>
    </main>
</body>
</html>
