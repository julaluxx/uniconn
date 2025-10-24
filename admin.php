<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// ดึงผู้ใช้ทั้งหมด
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// เปลี่ยนบทบาท
if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $user_id]);
    header('Location: admin.php');
    exit;
}

// ลบผู้ใช้
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    header('Location: admin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <h2 class="text-2xl font-bold mb-4">จัดการผู้ใช้</h2>
    <table class="table w-full">
        <thead>
            <tr>
                <th>ชื่อผู้ใช้</th>
                <th>อีเมล</th>
                <th>บทบาท</th>
                <th>การกระทำ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['role']; ?></td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" class="select select-bordered">
                                <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>User</option>
                                <option value="moderator" <?php if ($user['role'] == 'moderator') echo 'selected'; ?>>Moderator</option>
                                <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                            <button class="btn btn-info" type="submit" name="update_role">อัปเดต</button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button class="btn btn-error" type="submit" name="delete_user">ลบ</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>