<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $content, $user_id, $category_id]);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>สร้างกระทู้ใหม่ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 flex flex-col">

    <!-- Navbar -->
    <nav class="navbar bg-primary text-primary-content shadow-lg px-6">
        <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
            <!-- ซ้าย: โลโก้ -->
            <div class="flex-none">
                <a class="btn btn-ghost normal-case text-2xl font-bold tracking-wide" href="index.php">UniConnect</a>
            </div>

            <!-- กลาง: เมนู -->
            <div class="flex-1 flex justify-center space-x-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-ghost hover:bg-primary-focus">โปรไฟล์</a>
                    <?php if ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin'): ?>
                        <a href="moderate.php" class="btn btn-ghost hover:bg-primary-focus">จัดการกระทู้</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="admin.php" class="btn btn-ghost hover:bg-primary-focus">จัดการผู้ใช้</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ขวา: ปุ่มเข้าสู่ระบบ/ออกจากระบบ -->
            <div class="flex-none">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="btn btn-secondary">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex justify-center items-center p-6">
        <div class="card w-full max-w-2xl bg-base-100 shadow-2xl transition-all hover:shadow-3xl">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold mb-4 text-primary">📝 สร้างกระทู้ใหม่</h2>
                <form method="POST" class="space-y-4">
                    <div class="form-control">
                        <label class="label font-semibold text-gray-700"><span class="label-text">หัวข้อ</span></label>
                        <input type="text" name="title"
                            class="input input-bordered w-full focus:ring focus:ring-primary/30" required />
                    </div>

                    <div class="form-control">
                        <label class="label font-semibold text-gray-700"><span
                                class="label-text">หมวดหมู่</span></label>
                        <select name="category_id"
                            class="select select-bordered w-full focus:ring focus:ring-primary/30" required>
                            <option disabled selected>เลือกหมวดหมู่</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label font-semibold text-gray-700"><span class="label-text">เนื้อหา</span></label>
                        <textarea name="content" rows="6"
                            class="textarea textarea-bordered w-full focus:ring focus:ring-primary/30"
                            placeholder="พิมพ์เนื้อหากระทู้ของคุณที่นี่..." required></textarea>
                    </div>

                    <div class="form-control mt-6">
                        <button
                            class="btn btn-primary w-full text-lg font-semibold transition-all hover:scale-105 hover:shadow-md"
                            type="submit">
                            โพสต์กระทู้ 🚀
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300">
        <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
    </footer>
</body>

</html>