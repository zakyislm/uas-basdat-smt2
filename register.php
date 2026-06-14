<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = 'Alamat email sudah terdaftar. Silakan gunakan email lain atau masuk.';
    } else {
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($insert_stmt->execute()) {
            $new_user_id = $conn->insert_id;
            log_action($conn, $new_user_id, "Mendaftar akun baru dengan email $email");
            
            $_SESSION['flash_message'] = 'Pendaftaran berhasil! Silakan masuk.';
            header("Location: login.php");
            exit();
        } else {
            $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - Dealer Motor</title>
</head>
<body>
    <header>
        <h1>Dealer Motor</h1>
        <nav><a href="index.php">Kembali ke Beranda</a></nav>
    </header>
    <hr>

    <main>
        <h2>Pendaftaran Akun</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div>
                <label for="username">Nama Pengguna:</label><br>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Kata Sandi:</label><br>
                <input type="password" id="password" name="password" required>
            </div>
            <br>
            <button type="submit">Daftar</button>
        </form>
    </main>
</body>
</html>
