<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /");
    exit();
}
$action = isset($_POST['action']) ? $_POST['action'] : '';
$notif_ids = isset($_POST['notif_ids']) ? $_POST['notif_ids'] : [];
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
if ($action === 'read_all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR target_role = ?");
    $stmt->bind_param("is", $uid, $role);
    $stmt->execute();
} elseif ($action === 'delete_all') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ? OR target_role = ?");
    $stmt->bind_param("is", $uid, $role);
    $stmt->execute();
} elseif ($action === 'read_selected' && !empty($notif_ids)) {
    $ids = implode(',', array_map('intval', $notif_ids));
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($ids) AND (user_id = ? OR target_role = ?)");
    $stmt->bind_param("is", $uid, $role);
    $stmt->execute();
} elseif ($action === 'delete_selected' && !empty($notif_ids)) {
    $ids = implode(',', array_map('intval', $notif_ids));
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id IN ($ids) AND (user_id = ? OR target_role = ?)");
    $stmt->bind_param("is", $uid, $role);
    $stmt->execute();
}
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
header("Location: " . $referer);
exit();
