<?php
require_once 'config.php';

// Fetch available motorcycles (stock > 0)
$sql = "SELECT * FROM motorcycles WHERE stock > 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav>
            <a href="index.php">Beranda</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner'): ?>
                    | <a href="admin.php">Panel Admin (Transaksi)</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'owner'): ?>
                    | <a href="owner.php">Panel Owner (Barang)</a>
                <?php endif; ?>
                
                | <a href="logout.php">Keluar (<?= htmlspecialchars($_SESSION['username']) ?> - <?= strtoupper($_SESSION['role']) ?>)</a>
                
                <?php if ($_SESSION['is_verified'] == 1): ?>
                    <span>[Terverifikasi]</span>
                <?php else: ?>
                    <span>[Belum Terverifikasi]</span>
                <?php endif; ?>
            <?php else: ?>
                | <a href="login.php">Masuk</a>
                | <a href="register.php">Daftar</a>
            <?php endif; ?>
        </nav>
    </header>
    <hr>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <p><strong>[Info]</strong> <?= $_SESSION['flash_message'] ?></p>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <main>
        <h2>Daftar Motor Tersedia</h2>
        <?php if ($result->num_rows > 0): ?>
            <ul>
                <?php while($motor = $result->fetch_assoc()): ?>
                    <li>
                        <strong><?= htmlspecialchars($motor['make'] . ' ' . $motor['model']) ?></strong> (<?= $motor['year'] ?>)<br>
                        Harga: Rp <?= number_format($motor['price'], 0, ',', '.') ?><br>
                        Sisa Stok: <strong><?= $motor['stock'] ?> unit</strong><br>
                        Deskripsi: <?= htmlspecialchars($motor['description']) ?><br>
                        <a href="checkout.php?id=<?= $motor['id'] ?>">Pesan Motor Ini</a>
                    </li>
                    <br>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Maaf, saat ini tidak ada motor yang tersedia atau stok habis.</p>
        <?php endif; ?>
    </main>
</body>
</html>
