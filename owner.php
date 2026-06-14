<?php
require_once 'config.php';

// Check if user is owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    $_SESSION['flash_message'] = 'Akses ditolak. Halaman ini HANYA untuk owner.';
    header("Location: index.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $d_stmt = $conn->prepare("DELETE FROM motorcycles WHERE id = ?");
    $d_stmt->bind_param("i", $del_id);
    $d_stmt->execute();
    log_action($conn, $_SESSION['user_id'], "Menghapus data motor ID $del_id");
    $_SESSION['flash_message'] = "Barang berhasil dihapus.";
    header("Location: owner.php");
    exit();
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = intval($_POST['year']);
    $price = floatval($_POST['price']);
    $description = $_POST['description'];
    $stock = intval($_POST['stock']);

    if (isset($_POST['motor_id']) && !empty($_POST['motor_id'])) {
        // Edit mode
        $id = intval($_POST['motor_id']);
        $u_stmt = $conn->prepare("UPDATE motorcycles SET make=?, model=?, year=?, price=?, description=?, stock=? WHERE id=?");
        $u_stmt->bind_param("ssidsii", $make, $model, $year, $price, $description, $stock, $id);
        $u_stmt->execute();
        log_action($conn, $_SESSION['user_id'], "Mengupdate data motor ID $id menjadi $make $model (Stok: $stock)");
        $_SESSION['flash_message'] = "Barang berhasil diupdate.";
    } else {
        // Add mode
        $i_stmt = $conn->prepare("INSERT INTO motorcycles (make, model, year, price, description, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $i_stmt->bind_param("ssidsi", $make, $model, $year, $price, $description, $stock);
        $i_stmt->execute();
        $new_id = $conn->insert_id;
        log_action($conn, $_SESSION['user_id'], "Menambahkan motor baru: $make $model (ID: $new_id)");
        $_SESSION['flash_message'] = "Barang baru berhasil ditambahkan.";
    }
    header("Location: owner.php");
    exit();
}

// Fetch Motorcycles
$motorcycles = $conn->query("SELECT * FROM motorcycles");

// Check if editing
$edit_motor = null;
if (isset($_GET['edit_id'])) {
    $e_stmt = $conn->prepare("SELECT * FROM motorcycles WHERE id = ?");
    $e_stmt->bind_param("i", intval($_GET['edit_id']));
    $e_stmt->execute();
    $edit_motor = $e_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Owner - Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav>
            <a href="index.php">Kembali ke Beranda</a>
            | <a href="admin.php">Ke Panel Admin (Kelola Transaksi)</a>
        </nav>
    </header>
    <hr>

    <main>
        <h2>Panel Owner (Kelola Data Barang)</h2>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <p style="color: blue;"><strong>[Info]</strong> <?= $_SESSION['flash_message'] ?></p>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <fieldset>
            <legend><?= $edit_motor ? 'Edit Barang' : 'Tambah Barang Baru' ?></legend>
            <form method="POST" action="owner.php">
                <?php if ($edit_motor): ?>
                    <input type="hidden" name="motor_id" value="<?= $edit_motor['id'] ?>">
                <?php endif; ?>
                
                <div>
                    <label>Merk:</label><br>
                    <input type="text" name="make" value="<?= $edit_motor ? htmlspecialchars($edit_motor['make']) : '' ?>" required>
                </div>
                <div>
                    <label>Model:</label><br>
                    <input type="text" name="model" value="<?= $edit_motor ? htmlspecialchars($edit_motor['model']) : '' ?>" required>
                </div>
                <div>
                    <label>Tahun:</label><br>
                    <input type="number" name="year" value="<?= $edit_motor ? $edit_motor['year'] : '2024' ?>" required>
                </div>
                <div>
                    <label>Harga (Rp):</label><br>
                    <input type="number" name="price" value="<?= $edit_motor ? $edit_motor['price'] : '' ?>" required>
                </div>
                <div>
                    <label>Stok:</label><br>
                    <input type="number" name="stock" value="<?= $edit_motor ? $edit_motor['stock'] : '1' ?>" min="0" required>
                </div>
                <div>
                    <label>Deskripsi:</label><br>
                    <textarea name="description" rows="3" cols="30"><?= $edit_motor ? htmlspecialchars($edit_motor['description']) : '' ?></textarea>
                </div>
                <br>
                <button type="submit"><?= $edit_motor ? 'Simpan Perubahan' : 'Tambah Barang' ?></button>
                <?php if ($edit_motor): ?>
                    <a href="owner.php"><button type="button">Batal Edit</button></a>
                <?php endif; ?>
            </form>
        </fieldset>

        <br>
        <h3>Daftar Seluruh Barang</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Merk & Model</th>
                    <th>Tahun</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $motorcycles->fetch_assoc()): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['make'] . ' ' . $m['model']) ?></td>
                        <td><?= $m['year'] ?></td>
                        <td>Rp <?= number_format($m['price'], 0, ',', '.') ?></td>
                        <td><?= $m['stock'] ?></td>
                        <td>
                            <a href="owner.php?edit_id=<?= $m['id'] ?>">Edit</a> | 
                            <a href="owner.php?delete_id=<?= $m['id'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini? Semua pesanan terkait mungkin ikut terhapus.')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
