<?php
session_start();
include 'config.php';

// ดึงหมวดหมู่
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// ดึงข้อมูลผู้ใช้ (ถ้าเข้าสู่ระบบ)
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

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
    <!-- เมนูนำทาง -->
    <div class="navbar bg-primary text-primary-content">
        <!-- ส่วนซ้าย: โลโก้ -->
        <div class="flex-none">
            <a class="btn btn-ghost text-xl" href="index.php">UniConnect</a>
        </div>
        <!-- ส่วนกลาง: เมนู -->
        <div class="flex-1 justify-center gap-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-ghost">โปรไฟล์</a>
                <?php if ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin'): ?>
                    <a href="moderate.php" class="btn btn-ghost">จัดการกระทู้</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="admin.php" class="btn btn-ghost">จัดการผู้ใช้</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <!-- ส่วนขวา: เข้าสู่ระบบ/ออกจากระบบ -->
        <div class="flex-none">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary">เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- เมนูบน -->
    <div class="flex justify-between items-center bg-base-200 w-full flex space-x-4 p-4">
        <div id="search" class="w-full">
            <form action="index.php" method="GET" class="form-control">
                <input type="text" name="search" placeholder="ค้นหา" class="input input-bordered w-full"
                    value="<?php echo htmlspecialchars($search); ?>" />
            </form>
        </div>
        <div id="create-new-post" class="flex-shrink-0">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="post.php" class="btn btn-primary">สร้างกระทู้ใหม่</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- เนื้อหาหลัก -->
    <div class="flex">
        <!-- เมนูซ้าย -->
        <div class="w-1/4 p-4">
            <!-- โปรไฟล์ -->
            <div class="card bg-base-200 w-full rounded-box mb-4 text-center">
                <?php if ($user): ?>
                    <div class="card-body flex items-center space-x-4 p-4">
                        <div>
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p class="text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4">
                        <p class="text-sm">กรุณาเข้าสู่ระบบเพื่อดูโปรไฟล์</p>
                        <a href="login.php" class="btn btn-secondary btn-sm mt-2">เข้าสู่ระบบ</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- หมวดหมู่ -->
            <ul class="menu bg-base-200 w-full rounded-box">
                <?php foreach ($categories as $cat): ?>
                    <li><a href="index.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>

            <!-- สถิติ -->
            <ul class="menu bg-base-200 w-full rounded-box">
                <?php
                // display statistic of this website
                ?>
            </ul>
        </div>

        <!-- ส่วนกลาง: กระทู้ -->
        <div class="w-3/4 p-4">
            <h2 class="text-2xl font-bold mb-4">กระทู้ล่าสุด</h2>
            <?php foreach ($posts as $post): ?>
                <div class="card bg-base-100 shadow-xl mb-4">
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            <?php if ($post['pinned']): ?><span class="badge badge-primary">ปักหมุด</span><?php endif; ?>
                        </h2>
                        <p>โดย: <?php echo htmlspecialchars($post['username']); ?> | หมวด: <?php echo htmlspecialchars($post['category']); ?> | วันที่:
                            <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> | ดู: <?php echo $post['views']; ?>
                        </p>
                        <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- footer -->
    <footer class="footer p-10 bg-neutral text-neutral-content">
        <div>© UniConnect 2025 - All rights reserved</div>
    </footer>

    <!-- AJAX สำหรับ notification พื้นฐาน (เช็คกระทู้ใหม่ทุก 10 วินาที) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <script>
            setInterval(function () {
                fetch('notifications.php').then(response => response.text()).then(data => {
                    if (data) alert(data); // แสดงแจ้งเตือน
                });
            }, 10000);
        </script>
    <?php endif; ?>
</body>
</html>