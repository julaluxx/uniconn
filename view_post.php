<?php
session_start();
include 'config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = $_GET['id'];

// อัปเดต views
$pdo->exec("UPDATE posts SET views = views + 1 WHERE id = $post_id");

// ดึงกระทู้
$stmt = $pdo->prepare("SELECT p.*, u.username, c.name as category FROM posts p JOIN users u ON p.user_id = u.id JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.hidden = FALSE");
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) {
    header('Location: index.php');
    exit;
}

// ดึงความคิดเห็น
$stmt = $pdo->prepare("SELECT cm.*, u.username FROM comments cm JOIN users u ON cm.user_id = u.id WHERE cm.post_id = ? ORDER BY cm.created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// ตอบกระทู้
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && isset($_POST['content'])) {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content]);
    header("Location: view_post.php?id=$post_id");
    exit;
}

// รายงานกระทู้
if (isset($_POST['report_post'])) {
    $reason = $_POST['reason'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, user_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $reason]);
}

// รายงานความคิดเห็น
if (isset($_POST['report_comment'])) {
    $reason = $_POST['reason'];
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO reports (comment_id, user_id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $user_id, $reason]);
}

// Moderator: ลบกระทู้
if (isset($_POST['delete_post']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')) {
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    header('Location: index.php');
    exit;
}

// Moderator: ซ่อนกระทู้
if (isset($_POST['hide_post']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')) {
    $stmt = $pdo->prepare("UPDATE posts SET hidden = TRUE WHERE id = ?");
    $stmt->execute([$post_id]);
    header('Location: index.php');
    exit;
}

// Moderator: ปักหมุด
if (isset($_POST['pin_post']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')) {
    $pinned = $_POST['pinned'] ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE posts SET pinned = ? WHERE id = ?");
    $stmt->execute([$pinned, $post_id]);
    header("Location: view_post.php?id=$post_id");
    exit;
}

// Moderator: ลบความคิดเห็น
if (isset($_POST['delete_comment']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')) {
    $comment_id = $_POST['comment_id'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    header("Location: view_post.php?id=$post_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?php echo $post['title']; ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <div class="card bg-base-100 shadow-xl mb-4">
        <div class="card-body">
            <h2 class="card-title"><?php echo $post['title']; ?> <?php if ($post['pinned']): ?><span class="badge badge-primary">ปักหมุด</span><?php endif; ?></h2>
            <p>โดย: <?php echo $post['username']; ?> | หมวด: <?php echo $post['category']; ?> | วันที่: <?php echo $post['created_at']; ?> | ดู: <?php echo $post['views']; ?></p>
            <p><?php echo nl2br($post['content']); ?></p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="report_post" value="1">
                    <textarea name="reason" class="textarea textarea-bordered" placeholder="เหตุผลในการรายงาน"></textarea>
                    <button class="btn btn-warning mt-2" type="submit">รายงานกระทู้</button>
                </form>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')): ?>
                <form method="POST" class="mt-4">
                    <button class="btn btn-error" type="submit" name="delete_post">ลบกระทู้</button>
                    <button class="btn btn-warning" type="submit" name="hide_post">ซ่อนกระทู้</button>
                    <input type="hidden" name="pin_post" value="1">
                    <button class="btn btn-info" type="submit" name="pinned" value="<?php echo $post['pinned'] ? 0 : 1; ?>"><?php echo $post['pinned'] ? 'ยกเลิกปักหมุด' : 'ปักหมุด'; ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="text-xl font-bold mb-2">ความคิดเห็น</h3>
    <?php foreach ($comments as $cm): ?>
        <div class="card bg-base-100 shadow mb-2">
            <div class="card-body">
                <p class="font-bold"><?php echo $cm['username']; ?> (<?php echo $cm['created_at']; ?>)</p>
                <p><?php echo nl2br($cm['content']); ?></p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="report_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                        <textarea name="reason" class="textarea textarea-bordered w-full" placeholder="เหตุผลในการรายงาน"></textarea>
                        <button class="btn btn-warning mt-2" type="submit">รายงานความคิดเห็น</button>
                    </form>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin')): ?>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="delete_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $cm['id']; ?>">
                        <button class="btn btn-error" type="submit">ลบความคิดเห็น</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <h3 class="text-xl font-bold mt-4">ตอบกระทู้</h3>
        <form method="POST" class="mt-2">
            <textarea name="content" class="textarea textarea-bordered w-full" placeholder="แสดงความคิดเห็น" required></textarea>
            <button class="btn btn-primary mt-2" type="submit">ส่ง</button>
        </form>
    <?php else: ?>
        <p>กรุณา<a href="login.php" class="link link-primary">เข้าสู่ระบบ</a>เพื่อตอบกระทู้</p>
    <?php endif; ?>
</body>
</html>