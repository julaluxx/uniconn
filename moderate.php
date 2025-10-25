<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ผู้ใช้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
    header('Location: index.php');
    exit;
}

// ดึงรายงาน
$stmt = $pdo->query("
    SELECT r.*, u.username AS reporter, p.title AS post_title, cm.content AS comment_content
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN posts p ON r.post_id = p.id
    LEFT JOIN comments cm ON r.comment_id = cm.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");
$reports = $stmt->fetchAll();

// ดึงหมวดหมู่ทั้งหมด
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ดึงกระทู้ทั้งหมด
$posts = $pdo->query("
    SELECT p.*, u.username, c.name AS category_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();

// จัดการฟังก์ชันต่าง ๆ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resolve'])) {
        $pdo->prepare("UPDATE reports SET status='resolved' WHERE id=?")->execute([$_POST['report_id']]);
    }
    if (isset($_POST['toggle_hidden'])) {
        $pdo->prepare("UPDATE posts SET hidden = NOT hidden WHERE id=?")->execute([$_POST['post_id']]);
    }
    if (isset($_POST['toggle_pinned'])) {
        $pdo->prepare("UPDATE posts SET pinned = NOT pinned WHERE id=?")->execute([$_POST['post_id']]);
    }
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        $desc = trim($_POST['category_description']) ?: null;
        if ($name)
            $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)")->execute([$name, $desc]);
    }
    if (isset($_POST['edit_category'])) {
        $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?")->execute([
            trim($_POST['category_name']),
            trim($_POST['category_description']) ?: null,
            $_POST['category_id']
        ]);
    }
    if (isset($_POST['delete_category'])) {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$_POST['category_id']]);
    }
    header('Location: moderate.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>จัดการกระทู้ - UniConnect</title>
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
                        <a href="moderate.php" class="btn btn-success hover:bg-primary-focus">จัดการกระทู้</a>
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

    <!-- Main Content -->
    <main class="flex-grow container mx-auto p-6 max-w-6xl">
        <h1 class="text-3xl font-bold text-primary mb-6">🛠️ จัดการระบบ (Moderator Panel)</h1>

        <!-- รายงาน -->
        <section class="mb-10">
            <h2 class="text-2xl font-semibold mb-4">🚨 รายงานที่รอดำเนินการ</h2>

            <?php if (empty($reports)): ?>
                <div class="alert alert-info shadow-lg">
                    <span>ไม่มีรายงานที่รอดำเนินการ</span>
                </div>
            <?php else: ?>
                <?php foreach ($reports as $r): ?>
                    <div class="card bg-base-100 shadow-md mb-4">
                        <div class="card-body">
                            <p class="text-sm text-gray-600">รายงานโดย: <b><?php echo htmlspecialchars($r['reporter']); ?></b>
                            </p>
                            <p>เหตุผล: <?php echo htmlspecialchars($r['reason'] ?: '—'); ?></p>
                            <?php if ($r['post_id']): ?>
                                <p>กระทู้: <a href="view_post.php?id=<?php echo $r['post_id']; ?>"
                                        class="link link-primary"><?php echo htmlspecialchars($r['post_title']); ?></a></p>
                            <?php else: ?>
                                <p>ความคิดเห็น: "<?php echo htmlspecialchars(substr($r['comment_content'], 0, 80)); ?>..."</p>
                            <?php endif; ?>

                            <button class="btn btn-success btn-sm mt-2"
                                onclick="showModal('resolve_<?php echo $r['id']; ?>')">แก้ไขแล้ว</button>

                            <!-- Modal -->
                            <dialog id="resolve_<?php echo $r['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg">✅ ยืนยันการแก้ไขรายงาน</h3>
                                    <p class="py-2">ต้องการเปลี่ยนสถานะรายงานนี้เป็น “แก้ไขแล้ว” หรือไม่?</p>
                                    <form method="POST">
                                        <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                        <div class="modal-action">
                                            <button class="btn btn-success" name="resolve">ยืนยัน</button>
                                            <button type="button" class="btn"
                                                onclick="closeModal('resolve_<?php echo $r['id']; ?>')">ยกเลิก</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- หมวดหมู่ -->
        <section class="mb-10">
            <h2 class="text-2xl font-semibold mb-4">📚 จัดการหมวดหมู่</h2>
            <button class="btn btn-primary mb-4" onclick="showModal('addCategory')">➕ เพิ่มหมวดหมู่</button>

            <dialog id="addCategory" class="modal">
                <div class="modal-box">
                    <h3 class="font-bold text-lg">เพิ่มหมวดหมู่ใหม่</h3>
                    <form method="POST">
                        <input type="text" name="category_name" placeholder="ชื่อหมวดหมู่"
                            class="input input-bordered w-full my-2" required>
                        <textarea name="category_description" placeholder="คำอธิบาย (ถ้ามี)"
                            class="textarea textarea-bordered w-full"></textarea>
                        <div class="modal-action">
                            <button class="btn btn-primary" name="add_category">เพิ่ม</button>
                            <button type="button" class="btn" onclick="closeModal('addCategory')">ยกเลิก</button>
                        </div>
                    </form>
                </div>
            </dialog>

            <div class="card bg-base-100 shadow-md">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>ชื่อหมวดหมู่</th>
                                <th>คำอธิบาย</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['description'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info"
                                            onclick="showModal('edit_<?php echo $c['id']; ?>')">แก้ไข</button>
                                        <button class="btn btn-sm btn-error"
                                            onclick="showModal('delete_<?php echo $c['id']; ?>')">ลบ</button>

                                        <!-- Modal แก้ไข -->
                                        <dialog id="edit_<?php echo $c['id']; ?>" class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">แก้ไขหมวดหมู่</h3>
                                                <form method="POST">
                                                    <input type="hidden" name="category_id" value="<?php echo $c['id']; ?>">
                                                    <input type="text" name="category_name"
                                                        value="<?php echo htmlspecialchars($c['name']); ?>"
                                                        class="input input-bordered w-full my-2" required>
                                                    <textarea name="category_description"
                                                        class="textarea textarea-bordered w-full"><?php echo htmlspecialchars($c['description'] ?? ''); ?></textarea>
                                                    <div class="modal-action">
                                                        <button class="btn btn-info" name="edit_category">บันทึก</button>
                                                        <button type="button" class="btn"
                                                            onclick="closeModal('edit_<?php echo $c['id']; ?>')">ยกเลิก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </dialog>

                                        <!-- Modal ลบ -->
                                        <dialog id="delete_<?php echo $c['id']; ?>" class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">⚠️ ยืนยันการลบหมวดหมู่</h3>
                                                <p>คุณต้องการลบ "<?php echo htmlspecialchars($c['name']); ?>" หรือไม่?</p>
                                                <form method="POST">
                                                    <input type="hidden" name="category_id" value="<?php echo $c['id']; ?>">
                                                    <div class="modal-action">
                                                        <button class="btn btn-error" name="delete_category">ลบ</button>
                                                        <button type="button" class="btn"
                                                            onclick="closeModal('delete_<?php echo $c['id']; ?>')">ยกเลิก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </dialog>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- กระทู้ -->
        <section>
            <h2 class="text-2xl font-semibold mb-4">🧵 จัดการกระทู้</h2>
            <div class="card bg-base-100 shadow-md">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>หัวข้อ</th>
                                <th>หมวดหมู่</th>
                                <th>ผู้โพสต์</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td><a href="view_post.php?id=<?php echo $p['id']; ?>"
                                            class="link link-primary"><?php echo htmlspecialchars($p['title']); ?></a></td>
                                    <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['username']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></td>
                                    <td>
                                        <?php if ($p['pinned']): ?><span
                                                class="badge badge-info">ปักหมุด</span><?php endif; ?>
                                        <?php if ($p['hidden']): ?><span
                                                class="badge badge-warning">ซ่อน</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                            onclick="showModal('pin_<?php echo $p['id']; ?>')"><?php echo $p['pinned'] ? 'ยกเลิกปักหมุด' : 'ปักหมุด'; ?></button>
                                        <button class="btn btn-sm btn-warning"
                                            onclick="showModal('hide_<?php echo $p['id']; ?>')"><?php echo $p['hidden'] ? 'ยกเลิกซ่อน' : 'ซ่อน'; ?></button>

                                        <!-- Modal -->
                                        <dialog id="pin_<?php echo $p['id']; ?>" class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">ปักหมุดกระทู้</h3>
                                                <p>คุณต้องการ<?php echo $p['pinned'] ? 'ยกเลิก' : ''; ?>ปักหมุดกระทู้
                                                    "<?php echo htmlspecialchars($p['title']); ?>" หรือไม่?</p>
                                                <form method="POST">
                                                    <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                                                    <div class="modal-action">
                                                        <button class="btn btn-primary" name="toggle_pinned">ยืนยัน</button>
                                                        <button type="button" class="btn"
                                                            onclick="closeModal('pin_<?php echo $p['id']; ?>')">ยกเลิก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </dialog>

                                        <dialog id="hide_<?php echo $p['id']; ?>" class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">ซ่อนกระทู้</h3>
                                                <p>คุณต้องการ<?php echo $p['hidden'] ? 'ยกเลิกการซ่อน' : 'ซ่อน'; ?>กระทู้
                                                    "<?php echo htmlspecialchars($p['title']); ?>" หรือไม่?</p>
                                                <form method="POST">
                                                    <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                                                    <div class="modal-action">
                                                        <button class="btn btn-warning" name="toggle_hidden">ยืนยัน</button>
                                                        <button type="button" class="btn"
                                                            onclick="closeModal('hide_<?php echo $p['id']; ?>')">ยกเลิก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </dialog>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300 mt-10">
        <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
    </footer>

    <!-- JS -->
    <script>
        function showModal(id) {
            document.getElementById(id).showModal();
        }
        function closeModal(id) {
            document.getElementById(id).close();
        }
    </script>

</body>

</html>