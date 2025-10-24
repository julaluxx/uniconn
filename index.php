<?php
session_start();
include 'config.php';

// ดึงหมวดหมู่
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// การค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// ดึงกระทู้ (รองรับค้นหาและหมวดหมู่)
$query = "SELECT p.*, u.username, c.name as category FROM posts p JOIN users u ON p.user_id = u.id JOIN categories c ON p.category_id = c.id WHERE p.hidden = FALSE";
$params = [];
if ($search) {
    $query .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category_filter) {
    $query .= " AND c.id = ?";
    $params[] = $category_filter;
}
$query .= " ORDER BY p.pinned DESC, p.created_at DESC LIMIT 20";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100">
    <div class="navbar bg-primary text-primary-content">
        <div class="flex-1">
            <a class="btn btn-ghost text-xl" href="index.php">UniConnect</a>
        </div>
        <div class="flex-none gap-2">
            <form action="index.php" method="GET" class="form-control">
                <input type="text" name="search" placeholder="ค้นหา" class="input input-bordered w-24 md:w-auto" value="<?php echo htmlspecialchars($search); ?>" />
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full">
                            <img src="<?php echo $_SESSION['profile_pic'] ?? 'default.jpg'; ?>" />
                        </div>
                    </label>
                    <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                        <li><a href="profile.php">โปรไฟล์</a></li>
                        <?php if ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin'): ?>
                            <li><a href="moderate.php">จัดการกระทู้</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li><a href="admin.php">จัดการผู้ใช้</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">ออกจากระบบ</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary">เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex">
        <!-- เมนูซ้าย -->
        <div class="w-1/4 p-4">
            <ul class="menu bg-base-200 w-full rounded-box">
                <?php foreach ($categories as $cat): ?>
                    <li><a href="index.php?category=<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- ส่วนกลาง: กระทู้ -->
        <div class="w-3/4 p-4">
            <h2 class="text-2xl font-bold mb-4">กระทู้ล่าสุด</h2>
            <?php foreach ($posts as $post): ?>
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <h2 class="card-title"><a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a> <?php if ($post['pinned']): ?><span class="badge badge-primary">ปักหมุด</span><?php endif; ?></h2>
                        <p>โดย: <?php echo $post['username']; ?> | หมวด: <?php echo $post['category']; ?> | วันที่: <?php echo $post['created_at']; ?> | ดู: <?php echo $post['views']; ?></p>
                        <p><?php echo substr($post['content'], 0, 100) . '...'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="post.php" class="btn btn-primary">สร้างกระทู้ใหม่</a>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer p-10 bg-neutral text-neutral-content">
        <div>© UniConnect 2025 - All rights reserved</div>
    </footer>

    <!-- AJAX สำหรับ notification พื้นฐาน (เช็คกระทู้ใหม่ทุก 10 วินาที) -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        setInterval(function() {
            fetch('notifications.php').then(response => response.text()).then(data => {
                if (data) alert(data);  // แสดงแจ้งเตือนง
            });
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>