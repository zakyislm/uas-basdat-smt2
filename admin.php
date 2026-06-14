<?php
require_once 'config.php';

// Check if user is admin or owner
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    $_SESSION['flash_message'] = 'Akses ditolak. Halaman ini hanya untuk admin dan owner.';
    header("Location: index.php");
    exit();
}

// Handle verify action
if (isset($_GET['verify_user_id'])) {
    $verify_id = intval($_GET['verify_user_id']);
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $verify_id);
    $stmt->execute();
    log_action($conn, $_SESSION['user_id'], "Memverifikasi user ID $verify_id");
    $_SESSION['flash_message'] = "Pengguna berhasil diverifikasi.";
    header("Location: admin.php");
    exit();
}

// Handle delete transaction
if (isset($_GET['delete_transaction'])) {
    $trx_id = intval($_GET['delete_transaction']);
    
    // Get transaction details to return stock
    $b_stmt = $conn->prepare("SELECT motorcycle_id, quantity FROM transactions WHERE id = ?");
    $b_stmt->bind_param("i", $trx_id);
    $b_stmt->execute();
    $b_res = $b_stmt->get_result();
    
    if ($b_res->num_rows > 0) {
        $trx = $b_res->fetch_assoc();
        
        // Return stock
        $m_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock + ? WHERE id = ?");
        $m_stmt->bind_param("ii", $trx['quantity'], $trx['motorcycle_id']);
        $m_stmt->execute();
        
        // Delete transaction
        $d_stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        $d_stmt->bind_param("i", $trx_id);
        $d_stmt->execute();
        
        log_action($conn, $_SESSION['user_id'], "Menghapus transaksi ID $trx_id dan mengembalikan $trx[quantity] stok ke motor ID $trx[motorcycle_id]");
        
        $_SESSION['flash_message'] = "Transaksi dihapus dan stok telah dikembalikan.";
    }
    header("Location: admin.php");
    exit();
}

// Handle edit transaction status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    $trx_id = intval($_POST['transaction_id']);
    $new_status = $_POST['status'];
    $new_payment_status = $_POST['payment_status'];
    
    $u_stmt = $conn->prepare("UPDATE transactions SET status = ?, payment_status = ? WHERE id = ?");
    $u_stmt->bind_param("ssi", $new_status, $new_payment_status, $trx_id);
    $u_stmt->execute();
    
    log_action($conn, $_SESSION['user_id'], "Mengubah transaksi ID $trx_id menjadi status: $new_status, pembayaran: $new_payment_status");
    
    $_SESSION['flash_message'] = "Data transaksi berhasil diperbarui.";
    header("Location: admin.php");
    exit();
}

// Handle quick edit stock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $m_id = intval($_POST['motorcycle_id']);
    $new_stock = intval($_POST['stock']);
    
    if ($new_stock >= 0) {
        $s_stmt = $conn->prepare("UPDATE motorcycles SET stock = ? WHERE id = ?");
        $s_stmt->bind_param("ii", $new_stock, $m_id);
        $s_stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Mengubah stok motor ID $m_id menjadi $new_stock");
        $_SESSION['flash_message'] = "Stok barang berhasil diperbarui.";
    } else {
        $_SESSION['flash_message'] = "Stok tidak boleh minus.";
    }
    header("Location: admin.php");
    exit();
}

// Fetch Data
$users = $conn->query("SELECT * FROM users");
$motorcycles = $conn->query("SELECT * FROM motorcycles");
$transactions = $conn->query("
    SELECT t.id, t.transaction_date, t.quantity, t.type, t.status, t.payment_status, u.username, m.make, m.model 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN motorcycles m ON t.motorcycle_id = m.id
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav>
            <a href="index.php">Kembali ke Beranda</a>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                | <a href="owner.php">Pergi ke Panel Owner</a>
            <?php endif; ?>
        </nav>
    </header>
    <hr>

    <main>
        <h2>Panel Admin (Kelola Transaksi & User)</h2>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <p style="color: blue;"><strong>[Info]</strong> <?= $_SESSION['flash_message'] ?></p>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <h3>Daftar Pengguna</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= strtoupper($u['role']) ?></td>
                        <td><?= $u['is_verified'] ? 'Terverifikasi' : 'Belum Verifikasi' ?></td>
                        <td>
                            <?php if (!$u['is_verified']): ?>
                                <a href="admin.php?verify_user_id=<?= $u['id'] ?>">Verifikasi</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Kelola Stok Cepat</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID Motor</th>
                    <th>Motor</th>
                    <th>Stok Saat Ini</th>
                    <th>Ubah Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $motorcycles->fetch_assoc()): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></td>
                        <td><?= $m['stock'] ?></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:inline;">
                                <input type="hidden" name="motorcycle_id" value="<?= $m['id'] ?>">
                                <input type="number" name="stock" value="<?= $m['stock'] ?>" min="0" required style="width: 60px;">
                                <button type="submit" name="update_stock">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Kelola Transaksi</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Pembeli</th>
                    <th>Barang</th>
                    <th>Tipe</th>
                    <th>Edit Transaksi</th>
                    <th>Hapus</th>
                </tr>
            </thead>
            <tbody>
                <?php while($t = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= $t['transaction_date'] ?></td>
                        <td><?= htmlspecialchars($t['username']) ?></td>
                        <td><?= htmlspecialchars($t['make'] . ' ' . $t['model']) ?> (x<?= $t['quantity'] ?>)</td>
                        <td><?= strtoupper($t['type']) ?></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:inline;">
                                <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                
                                Status Proses:
                                <select name="status">
                                    <option value="pending" <?= $t['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $t['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="cancelled" <?= $t['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                
                                <br>Status Bayar:
                                <select name="payment_status">
                                    <option value="unpaid" <?= $t['payment_status'] == 'unpaid' ? 'selected' : '' ?>>Belum Lunas</option>
                                    <option value="paid" <?= $t['payment_status'] == 'paid' ? 'selected' : '' ?>>Lunas</option>
                                    <option value="refunded" <?= $t['payment_status'] == 'refunded' ? 'selected' : '' ?>>Refund</option>
                                </select>
                                
                                <button type="submit" name="update_transaction">Update</button>
                            </form>
                        </td>
                        <td>
                            <a href="admin.php?delete_transaction=<?= $t['id'] ?>" onclick="return confirm('Yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan otomatis.')">Hapus (Kembalikan Stok)</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($_SESSION['role'] === 'owner'): ?>
            <?php
            $logs = $conn->query("
                SELECT l.*, u.username, u.role 
                FROM logs l 
                JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC 
                LIMIT 100
            ");
            ?>
            <br>
            <h3>Sistem Log (Khusus Owner)</h3>
            <div style="background: #1e1e1e; color: #00ff00; padding: 15px; border: 1px solid #000; height: 300px; overflow-y: scroll; font-family: monospace;">
                <?php while($log = $logs->fetch_assoc()): ?>
                    <?php 
                        $date = date('d:m:y', strtotime($log['created_at']));
                        $time = date('H:i:s', strtotime($log['created_at']));
                    ?>
                    <p style="margin: 5px 0;">
                        <?= $date ?> >> Logging: Changes detected on <?= $time ?> by <?= htmlspecialchars($log['username']) ?>#<?= htmlspecialchars($log['role']) ?><br>
                        <?= $date ?> >> Actions: <?= htmlspecialchars($log['action_detail']) ?>
                    </p>
                    <hr style="border: 0; border-top: 1px dashed #444; margin: 8px 0;">
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>
