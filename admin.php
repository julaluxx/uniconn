<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

// ดึง username ของ admin
$admin_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$admin_stmt->execute([$_SESSION['user_id']]);
$admin_user = $admin_stmt->fetch();

// ดึงผู้ใช้ทั้งหมด พร้อม search
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE 1";
$params = [];
if ($search) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// เปลี่ยนบทบาท
if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    if ($user_id != $_SESSION['user_id']) { // ห้ามแก้สิทธิ์ตัวเอง
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);
    }
    header('Location: admin.php');
    exit;
}

// ลบผู้ใช้
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user_id']) { // ห้ามลบตัวเอง
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-base-100 flex flex-col">

    <!-- Navbar -->
    <nav class="navbar bg-primary text-primary-content shadow-lg px-6">
        <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
            <div class="flex-none">
                <a href="index.php" class="btn btn-ghost normal-case text-2xl font-bold">UniConnect</a>
            </div>
            <div class="flex-1 flex justify-center gap-2">
                <a href="index.php" class="btn btn-ghost hover:bg-primary-focus">หน้าแรก</a>
                <a href="profile.php" class="btn btn-ghost hover:bg-primary-focus">โปรไฟล์</a>
                <a href="moderate.php" class="btn btn-ghost hover:bg-primary-focus">จัดการกระทู้</a>
                <a href="admin.php" class="btn btn-success hover:bg-primary-focus active">จัดการผู้ใช้</a>
            </div>
            <div class="flex-none">
                <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto max-w-7xl p-6 flex flex-col md:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="md:w-1/4 space-y-4">
            <div class="card bg-base-100 shadow-md p-4 text-center">
                <h3 class="font-bold text-lg mb-2">👤 ผู้ดูแลระบบ</h3>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($admin_user['username']); ?></p>
            </div>
            <div class="card bg-base-100 shadow-md p-4">
                <h3 class="font-bold mb-2">🔍 ค้นหาผู้ใช้</h3>
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" placeholder="ชื่อหรืออีเมล..." class="input input-bordered w-full"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary btn-sm">ค้นหา</button>
                </form>
            </div>
        </aside>

        <!-- Users table -->
        <section class="flex-1">
            <h2 class="text-2xl font-bold mb-4 text-primary">จัดการผู้ใช้ทั้งหมด</h2>

            <?php if (empty($users)): ?>
                <div class="alert alert-info shadow-lg">
                    <span>ไม่พบผู้ใช้ที่ตรงกับการค้นหา</span>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
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
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td class="flex flex-wrap gap-2">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="flex gap-2">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" class="select select-bordered select-sm">
                                                    <option value="user" <?php if ($user['role'] == 'user')
                                                        echo 'selected'; ?>>User
                                                    </option>
                                                    <option value="moderator" <?php if ($user['role'] == 'moderator')
                                                        echo 'selected'; ?>>Moderator</option>
                                                    <option value="admin" <?php if ($user['role'] == 'admin')
                                                        echo 'selected'; ?>>
                                                        Admin</option>
                                                </select>
                                                <button type="submit" name="update_role" class="btn btn-info btn-sm">อัปเดต</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการลบผู้ใช้นี้?');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-error btn-sm">ลบ</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">ไม่สามารถแก้ไขตัวเอง</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300">
        <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
    </footer>
</body>

</html>