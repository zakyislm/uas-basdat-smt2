<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    log_action($conn, $_SESSION['user_id'], "Melakukan logout dari sistem");
}

session_unset();
session_destroy();

session_start();
$_SESSION['flash_message'] = "Anda telah berhasil keluar.";
header("Location: index.php");
exit();
?>
