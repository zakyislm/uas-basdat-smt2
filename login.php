<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password_hash, is_verified, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_verified'] = $user['is_verified'];
            $_SESSION['role'] = $user['role']; // 'user', 'admin', or 'owner'
            
            log_action($conn, $user['id'], "Berhasil login ke dalam sistem");
            
            header("Location: index.php");
            exit();
        } else {
            $error = 'Kata sandi salah. Silakan periksa kembali.';
        }
    } else {
        $error = 'Email tidak terdaftar.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Masuk - Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav><a href="index.php">Kembali ke Beranda</a></nav>
    </header>
    <hr>

    <main>
        <h2>Halaman Masuk</h2>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <p style="color: green;"><?= $_SESSION['flash_message'] ?></p>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div>
                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Kata Sandi:</label><br>
                <input type="password" id="password" name="password" required>
            </div>
            <br>
            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
