<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    log_action($conn, $_SESSION['user_id'], "Logged out of system || Melakukan logout dari sistem");
}
session_unset();
session_regenerate_id(true);
$_SESSION['flash_message'] = __('You have successfully logged out.', 'Anda telah berhasil keluar.');
header("Location: index");
exit();
?>
