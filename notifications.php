<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];

// เช็คความคิดเห็นใหม่ในกระทู้ที่ผู้ใช้สร้าง (ตัวอย่างง่าย)
$stmt = $pdo->prepare("SELECT COUNT(*) as new FROM comments cm JOIN posts p ON cm.post_id = p.id WHERE p.user_id = ? AND cm.created_at > NOW() - INTERVAL 1 MINUTE");
$stmt->execute([$user_id]);
$new = $stmt->fetch()['new'];

if ($new > 0) {
    echo "มี $new ความคิดเห็นใหม่!";
}
?>