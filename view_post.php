<?php
session_start();
include 'config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = intval($_GET['id']); // ป้องกัน SQL Injection

// อัปเดตยอดวิว
$pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);

// ดึงข้อมูลกระทู้
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_pic, c.name AS category 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.hidden = FALSE
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

// ดึงความคิดเห็น
$stmt = $pdo->prepare("
    SELECT cm.*, u.username, u.profile_pic 
    FROM comments cm 
    JOIN users u ON cm.user_id = u.id 
    WHERE cm.post_id = ? 
    ORDER BY cm.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// เพิ่มความคิดเห็น
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'], $_POST['content'])) {
    $content = trim($_POST['content']);
    if ($content !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
        header("Location: view_post.php?id=$post_id");
        exit;
    }
}

// รายงานโพสต์
if (isset($_POST['report_post']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, user_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $_SESSION['user_id'], $_POST['reason']]);
}

// รายงานความคิดเห็น
if (isset($_POST['report_comment']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("INSERT INTO reports (comment_id, user_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['comment_id'], $_SESSION['user_id'], $_POST['reason']]);
}

// Moderator actions
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['moderator', 'admin'])) {
    if (isset($_POST['delete_post'])) {
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
        header('Location: index.php');
        exit;
    }
    if (isset($_POST['hide_post'])) {
        $pdo->prepare("UPDATE posts SET hidden = TRUE WHERE id = ?")->execute([$post_id]);
        header('Location: index.php');
        exit;
    }
    if (isset($_POST['pin_post'])) {
        $pinned = intval($_POST['pinned']);
        $pdo->prepare("UPDATE posts SET pinned = ? WHERE id = ?")->execute([$pinned, $post_id]);
        header("Location: view_post.php?id=$post_id");
        exit;
    }
    if (isset($_POST['delete_comment'])) {
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$_POST['comment_id']]);
        header("Location: view_post.php?id=$post_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 flex flex-col">

<!-- Navbar -->
<nav class="navbar bg-primary text-primary-content shadow-lg px-6">
    <div class="flex justify-between items-center w-full max-w-7xl mx-auto">
        <div class="flex-none">
            <a class="btn btn-ghost normal-case text-2xl font-bold tracking-wide" href="index.php">UniConnect</a>
        </div>
        <div class="flex-1 flex justify-center space-x-2">
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
<main class="flex-grow container mx-auto px-6 py-8 max-w-4xl">
    <!-- โพสต์ -->
    <div class="card bg-base-100 shadow-2xl p-6 mb-6">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold text-primary">
                <?php echo htmlspecialchars($post['title']); ?>
                <?php if ($post['pinned']): ?><span class="badge badge-primary ml-2">ปักหมุด</span><?php endif; ?>
            </h2>
            <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                <img src="<?php echo htmlspecialchars($post['profile_pic'] ?? 'default.jpg'); ?>" class="w-8 h-8 rounded-full border border-primary/30">
                <span>โดย <?php echo htmlspecialchars($post['username']); ?></span> •
                <span>หมวด: <?php echo htmlspecialchars($post['category']); ?></span> •
                <span>วันที่: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span> •
                <span>ดู: <?php echo $post['views']; ?></span>
            </div>
            <p class="mt-4 text-gray-700 whitespace-pre-line leading-relaxed"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

            <!-- ปุ่มของ Moderator -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['moderator', 'admin'])): ?>
                <form method="POST" class="mt-4 flex flex-wrap gap-2">
                    <button type="submit" name="delete_post" class="btn btn-error btn-sm">ลบกระทู้</button>
                    <button type="submit" name="hide_post" class="btn btn-warning btn-sm">ซ่อนกระทู้</button>
                    <button type="submit" name="pin_post" value="1" class="btn btn-info btn-sm">
                        <?php echo $post['pinned'] ? 'ยกเลิกปักหมุด' : 'ปักหมุด'; ?>
                    </button>
                    <input type="hidden" name="pinned" value="<?php echo $post['pinned'] ? 0 : 1; ?>">
                </form>
            <?php endif; ?>

            <!-- ปุ่มเปิด Modal รายงานโพสต์ -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-warning mt-4" onclick="showModal('reportPostModal')">🚨 รายงานกระทู้</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal รายงานโพสต์ -->
    <dialog id="reportPostModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-warning">🚨 รายงานกระทู้</h3>
            <form method="POST">
                <input type="hidden" name="report_post" value="1">
                <textarea name="reason" class="textarea textarea-bordered w-full" placeholder="เหตุผลในการรายงาน (ไม่บังคับ)"></textarea>
                <div class="modal-action">
                    <button class="btn btn-warning">ส่งรายงาน</button>
                    <button type="button" class="btn" onclick="closeModal('reportPostModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- ความคิดเห็น -->
    <section>
        <h3 class="text-xl font-bold text-primary mb-3">💬 ความคิดเห็น</h3>

        <?php if (empty($comments)): ?>
            <p class="text-gray-500 italic mb-4">ยังไม่มีความคิดเห็น</p>
        <?php else: ?>
            <?php foreach ($comments as $cm): ?>
                <div class="card bg-base-100 shadow p-4 mb-3">
                    <div class="flex items-center gap-3 mb-2">
                        <img src="<?php echo htmlspecialchars($cm['profile_pic'] ?? 'default.jpg'); ?>" class="w-10 h-10 rounded-full border border-primary/30">
                        <div>
                            <p class="font-bold"><?php echo htmlspecialchars($cm['username']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($cm['created_at'])); ?></p>
                        </div>
                    </div>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($cm['content'])); ?></p>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-warning btn-sm" onclick="showModal('reportComment-<?php echo $cm['id']; ?>')">🚨 รายงาน</button>

                            <!-- Modal รายงานความคิดเห็น -->
                            <dialog id="reportComment-<?php echo $cm['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg text-warning">🚨 รายงานความคิดเห็น</h3>
                                    <form method="POST">
                                        <input type="hidden" name="report_comment" value="1">
                                        <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                                        <textarea name="reason" class="textarea textarea-bordered w-full" placeholder="เหตุผลในการรายงาน (ไม่บังคับ)"></textarea>
                                        <div class="modal-action">
                                            <button class="btn btn-warning">ส่งรายงาน</button>
                                            <button type="button" class="btn" onclick="closeModal('reportComment-<?php echo $cm['id']; ?>')">ยกเลิก</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['moderator', 'admin'])): ?>
                            <form method="POST">
                                <input type="hidden" name="delete_comment" value="1">
                                <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                                <button class="btn btn-error btn-sm">ลบความคิดเห็น</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- เพิ่มความคิดเห็น -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <section class="mt-8">
            <h3 class="text-xl font-bold text-primary mb-2">✏️ เขียนความคิดเห็น</h3>
            <form method="POST">
                <textarea name="content" class="textarea textarea-bordered w-full" placeholder="พิมพ์ความคิดเห็นของคุณ..." required></textarea>
                <button class="btn btn-primary mt-2">ส่งความคิดเห็น</button>
            </form>
        </section>
    <?php else: ?>
        <p class="text-gray-600 mt-4">กรุณา <a href="login.php" class="link link-primary">เข้าสู่ระบบ</a> เพื่อแสดงความคิดเห็น</p>
    <?php endif; ?>
</main>

<!-- Footer -->
<footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300 mt-10">
    <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
</footer>

<!-- JS เปิด/ปิด Modal -->
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
