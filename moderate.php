<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'moderator' && $_SESSION['role'] != 'admin')) {
    header('Location: index.php');
    exit;
}

// ดึงรายงาน
$stmt = $pdo->query("SELECT r.*, u.username as reporter, p.title as post_title, cm.content as comment_content FROM reports r JOIN users u ON r.user_id = u.id LEFT JOIN posts p ON r.post_id = p.id LEFT JOIN comments cm ON r.comment_id = cm.id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
$reports = $stmt->fetchAll();

// แก้ไขสถานะรายงาน
if (isset($_POST['resolve'])) {
    $report_id = $_POST['report_id'];
    $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    $stmt->execute([$report_id]);
    header('Location: moderate.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>จัดการกระทู้ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <h2 class="text-2xl font-bold mb-4">คิวรายงาน</h2>
    <?php foreach ($reports as $report): ?>
        <div class="card bg-base-100 shadow-xl mb-4">
            <div class="card-body">
                <p>รายงานโดย: <?php echo $report['reporter']; ?> | เหตุผล: <?php echo $report['reason']; ?></p>
                <?php if ($report['post_id']): ?>
                    <p>กระทู้: <a href="view_post.php?id=<?php echo $report['post_id']; ?>"><?php echo $report['post_title']; ?></a></p>
                <?php elseif ($report['comment_id']): ?>
                    <p>ความคิดเห็น: <?php echo substr($report['comment_content'], 0, 100); ?>...</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                    <button class="btn btn-success" type="submit" name="resolve">แก้ไขแล้ว</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>