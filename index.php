<?php
session_start();
include 'config.php';

// ดึงหมวดหมู่
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ดึงข้อมูลผู้ใช้
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// ค้นหา / กรองหมวดหมู่
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "
    SELECT p.*, u.username, c.name AS category 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.hidden = FALSE
";
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
$query .= " ORDER BY p.pinned DESC, p.created_at DESC LIMIT 30";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>UniConnect - หน้าหลัก</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 flex flex-col">

    <!-- Navbar -->
    <nav class="navbar bg-primary text-primary-content shadow-lg px-6">
        <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
            <!-- โลโก้ -->
            <div class="flex-none">
                <a href="index.php" class="btn btn-ghost normal-case text-2xl font-bold tracking-wide">UniConnect</a>
            </div>

            <!-- เมนูกลาง -->
            <div class="flex-1 flex justify-center space-x-2">
                <a href="index.php" class="btn btn-ghost hover:bg-primary-focus">หน้าแรก</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-ghost hover:bg-primary-focus">โปรไฟล์</a>
                    <?php if (in_array($_SESSION['role'], ['moderator', 'admin'])): ?>
                        <a href="moderate.php" class="btn btn-ghost hover:bg-primary-focus">จัดการกระทู้</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="admin.php" class="btn btn-ghost hover:bg-primary-focus">จัดการผู้ใช้</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ปุ่มเข้าสู่ระบบ -->
            <div class="flex-none">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 🔍 ส่วนค้นหา + ปุ่มสร้างกระทู้ -->
    <div class="bg-base-200 p-4 flex flex-wrap justify-between items-center gap-4">
        <form action="index.php" method="GET" class="flex-grow">
            <input type="text" name="search" placeholder="🔍 ค้นหากระทู้..."
                class="input input-bordered w-full focus:ring focus:ring-primary/30"
                value="<?php echo htmlspecialchars($search); ?>" />
        </form>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="post.php" class="btn btn-primary flex items-center gap-2">
                ✏️ สร้างกระทู้ใหม่
            </a>
        <?php endif; ?>
    </div>

    <!-- 🧭 Layout หลัก -->
    <main class="flex-grow container mx-auto max-w-7xl p-6 flex flex-col md:flex-row gap-6">

        <!-- 🎭 Sidebar -->
        <aside class="md:w-1/4 space-y-4">

            <!-- โปรไฟล์ -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body items-center text-center">
                    <?php if ($user): ?>
                        <img src="<?php echo $user['profile_pic'] ?? 'assets/default.png'; ?>"
                            class="w-20 h-20 rounded-full mb-2 border" alt="profile">
                        <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php else: ?>
                        <p>เข้าสู่ระบบเพื่อดูโปรไฟล์ของคุณ</p>
                        <a href="login.php" class="btn btn-secondary btn-sm mt-2">เข้าสู่ระบบ</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- หมวดหมู่ -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h3 class="font-bold mb-2">📚 หมวดหมู่</h3>
                    <ul class="menu menu-compact">
                        <li><a href="index.php"
                                class="<?php echo $category_filter == '' ? 'active' : ''; ?>">ทั้งหมด</a></li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?category=<?php echo $cat['id']; ?>"
                                    class="<?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- สถิติ (ตกแต่งทีหลังได้) -->
            <div class="card bg-base-100 shadow-md p-4 text-center text-sm text-gray-600">
                <p>🌟 ยินดีต้อนรับสู่ UniConnect!</p>
                <p>โพสต์ทั้งหมด:
                    <?php echo $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); ?>
                </p>
                <p>คอมเม้นทั้งหมด:
                    <?php echo $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(); ?>
                </p>
            </div>

        </aside>

        <!-- 📄 ส่วนกลาง -->
        <section class="flex-1">
            <h2 class="text-2xl font-bold mb-4 text-primary">กระทู้ล่าสุด</h2>

            <?php if (empty($posts)): ?>
                <div class="alert alert-info shadow-lg">
                    <span>ไม่พบกระทู้ที่ตรงกับเงื่อนไข</span>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card bg-base-100 shadow-md mb-4 hover:shadow-lg transition">
                        <div class="card-body">
                            <h2 class="card-title flex flex-wrap gap-2">
                                <a href="view_post.php?id=<?php echo $post['id']; ?>" class="link link-primary">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                                <?php if ($post['pinned']): ?>
                                    <span class="badge badge-primary">📌 ปักหมุด</span>
                                <?php endif; ?>
                            </h2>
                            <p class="text-sm text-gray-500">
                                โดย: <span class="font-medium"><?php echo htmlspecialchars($post['username']); ?></span> |
                                หมวด: <?php echo htmlspecialchars($post['category']); ?> |
                                วันที่: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> |
                                👁️ <?php echo $post['views']; ?> ครั้ง
                            </p>
                            <p class="mt-2 text-gray-700">
                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 150))); ?>...
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- 🦶 Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300">
        <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
    </footer>

</body>

</html>