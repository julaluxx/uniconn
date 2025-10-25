<?php
$host = 'localhost';
$db = 'uniconnect';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ฟังก์ชันช่วยเหลือสำหรับอัปโหลดรูป
function uploadImage($file) {
    $target_dir = "uploads/";
    // ตรวจสอบว่าโฟลเดอร์ uploads มีอยู่หรือไม่
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // ตรวจสอบประเภทไฟล์
    if (!in_array($file['type'], $allowed_types)) {
        return false; // หรือสามารถ throw Exception ได้
    }

    // ตรวจสอบขนาดไฟล์
    if ($file['size'] > $max_size) {
        return false;
    }

    // สร้างชื่อไฟล์ที่ไม่ซ้ำ
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = uniqid('img_', true) . '.' . $extension;
    $target_file = $target_dir . $unique_name;

    // ย้ายไฟล์
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    return false;
}
?>