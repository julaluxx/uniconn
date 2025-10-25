<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ผู้ใช้
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'moderator' && $_SESSION['role'] != 'admin')) {
    header('Location: index.php');
    exit;
}

// ดึงรายงาน
$stmt = $pdo->query("SELECT r.*, u.username as reporter, p.title as post_title, cm.content as comment_content FROM reports r JOIN users u ON r.user_id = u.id LEFT JOIN posts p ON r.post_id = p.id LEFT JOIN comments cm ON r.comment_id = cm.id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
$reports = $stmt->fetchAll();

// ดึงหมวดหมู่ทั้งหมด
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// ดึงกระทู้ทั้งหมด
$stmt = $pdo->query("SELECT p.*, u.username, c.name as category_name FROM posts p JOIN users u ON p.user_id = u.id JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();

// แก้ไขสถานะรายงาน
if (isset($_POST['resolve'])) {
    $report_id = $_POST['report_id'];
    $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    $stmt->execute([$report_id]);
    header('Location: moderate.php');
    exit;
}

// ซ่อน/ยกเลิกซ่อนกระทู้
if (isset($_POST['toggle_hidden'])) {
    $post_id = $_POST['post_id'];
    $stmt = $pdo->prepare("UPDATE posts SET hidden = NOT hidden WHERE id = ?");
    $stmt->execute([$post_id]);
    header('Location: moderate.php');
    exit;
}

// ปักหมุด/ยกเลิกปักหมุดกระทู้
if (isset($_POST['toggle_pinned'])) {
    $post_id = $_POST['post_id'];
    $stmt = $pdo->prepare("UPDATE posts SET pinned = NOT pinned WHERE id = ?");
    $stmt->execute([$post_id]);
    header('Location: moderate.php');
    exit;
}

// เพิ่มหมวดหมู่ใหม่
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['category_description']) ?: null;

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            header('Location: moderate.php');
            exit;
        } catch (PDOException $e) {
            $error = "ไม่สามารถเพิ่มหมวดหมู่ได้: " . $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกชื่อหมวดหมู่";
    }
}

// แก้ไขหมวดหมู่
if (isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $name = trim($_POST['category_name']);
    $description = trim($_POST['category_description']) ?: null;

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $category_id]);
            header('Location: moderate.php');
            exit;
        } catch (PDOException $e) {
            $error = "ไม่สามารถแก้ไขหมวดหมู่ได้: " . $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกชื่อหมวดหมู่";
    }
}

// ลบหมวดหมู่
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        header('Location: moderate.php');
        exit;
    } catch (PDOException $e) {
        $error = "ไม่สามารถลบหมวดหมู่ได้: " . $e->getMessage();
    }
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
<body class="bg-base-100">
    <!-- เมนูนำทาง -->
    <div class="navbar bg-primary text-primary-content">
        <div class="flex-1">
            <a class="btn btn-ghost text-xl" href="index.php">UniConnect</a>
        </div>
        <div class="flex-none gap-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full">
                            <img src="<?php echo $_SESSION['profile_pic'] ?? '/assets/default.png'; ?>" />
                        </div>
                    </label>
                    <ul tabindex="0"
                        class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
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

    <div class="container p-4">
    <!-- รายงาน -->
    <h2 class="text-2xl font-bold mb-4">คิวรายงาน</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-error mb-4">
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    <?php if (empty($reports)): ?>
        <p class="text-gray-500">ไม่มีรายงานที่รอดำเนินการ</p>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <div class="card bg-base-100 shadow-xl mb-4">
                <div class="card-body">
                    <p>รายงานโดย: <?php echo htmlspecialchars($report['reporter']); ?> | เหตุผล: <?php echo htmlspecialchars($report['reason']); ?></p>
                    <?php if ($report['post_id']): ?>
                        <p>กระทู้: <a href="view_post.php?id=<?php echo $report['post_id']; ?>" class="link link-primary"><?php echo htmlspecialchars($report['post_title']); ?></a></p>
                    <?php elseif ($report['comment_id']): ?>
                        <p>ความคิดเห็น: <?php echo htmlspecialchars(substr($report['comment_content'], 0, 100)); ?>...</p>
                    <?php endif; ?>
                    <button class="btn btn-success" onclick="document.getElementById('resolve_report_<?php echo $report['id']; ?>').showModal()">แก้ไขแล้ว</button>
                    <!-- Modal สำหรับยืนยันการแก้ไขรายงาน -->
                    <dialog id="resolve_report_<?php echo $report['id']; ?>" class="modal">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg">ยืนยันการแก้ไขรายงาน</h3>
                            <p class="py-4">คุณต้องการเปลี่ยนสถานะรายงานนี้เป็น "แก้ไขแล้ว" หรือไม่?</p>
                            <form method="POST">
                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                <div class="modal-action">
                                    <button class="btn btn-success" type="submit" name="resolve">ยืนยัน</button>
                                    <button class="btn" type="button" onclick="document.getElementById('resolve_report_<?php echo $report['id']; ?>').close()">ยกเลิก</button>
                                </div>
                            </form>
                        </div>
                    </dialog>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- เพิ่มหมวดหมู่ -->
    <h2 class="text-2xl font-bold mb-4 mt-8">จัดการหมวดหมู่</h2>
    <button class="btn btn-primary mb-4" onclick="document.getElementById('add_category_modal').showModal()">เพิ่มหมวดหมู่ใหม่</button>
    <!-- Modal สำหรับเพิ่มหมวดหมู่ -->
    <dialog id="add_category_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">เพิ่มหมวดหมู่ใหม่</h3>
            <form method="POST">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ชื่อหมวดหมู่</span>
                    </label>
                    <input type="text" name="category_name" class="input input-bordered" required>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">คำอธิบาย (ถ้ามี)</span>
                    </label>
                    <textarea name="category_description" class="textarea textarea-bordered"></textarea>
                </div>
                <div class="modal-action">
                    <button type="submit" name="add_category" class="btn btn-primary">เพิ่ม</button>
                    <button type="button" class="btn" onclick="document.getElementById('add_category_modal').close()">ยกเลิก</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- ตารางหมวดหมู่ -->
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
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['description'] ?? 'ไม่มีคำอธิบาย'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="document.getElementById('edit_category_<?php echo $category['id']; ?>').showModal()">แก้ไข</button>
                            <button class="btn btn-sm btn-error" onclick="document.getElementById('delete_category_<?php echo $category['id']; ?>').showModal()">ลบ</button>
                            <!-- Modal สำหรับแก้ไขหมวดหมู่ -->
                            <dialog id="edit_category_<?php echo $category['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg">แก้ไขหมวดหมู่</h3>
                                    <form method="POST">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text">ชื่อหมวดหมู่</span>
                                            </label>
                                            <input type="text" name="category_name" value="<?php echo htmlspecialchars($category['name']); ?>" class="input input-bordered" required>
                                        </div>
                                        <div class="form-control">
                                            <label class="label">
                                                <span class="label-text">คำอธิบาย (ถ้ามี)</span>
                                            </label>
                                            <textarea name="category_description" class="textarea textarea-bordered"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="modal-action">
                                            <button type="submit" name="edit_category" class="btn btn-primary">บันทึก</button>
                                            <button type="button" class="btn" onclick="document.getElementById('edit_category_<?php echo $category['id']; ?>').close()">ยกเลิก</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                            <!-- Modal สำหรับลบหมวดหมู่ -->
                            <dialog id="delete_category_<?php echo $category['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg">ยืนยันการลบหมวดหมู่</h3>
                                    <p class="py-4">คุณต้องการลบหมวดหมู่ "<?php echo htmlspecialchars($category['name']); ?>" หรือไม่? การลบอาจส่งผลต่อกระทู้ที่เกี่ยวข้อง</p>
                                    <form method="POST">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <div class="modal-action">
                                            <button type="submit" name="delete_category" class="btn btn-error">ลบ</button>
                                            <button type="button" class="btn" onclick="document.getElementById('delete_category_<?php echo $category['id']; ?>').close()">ยกเลิก</button>
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

    <!-- กระทู้ทั้งหมด -->
    <h2 class="text-2xl font-bold mb-4 mt-8">จัดการกระทู้</h2>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>หัวข้อ</th>
                    <th>หมวดหมู่</th>
                    <th>ผู้โพสต์</th>
                    <th>วันที่สร้าง</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><a href="view_post.php?id=<?php echo $post['id']; ?>" class="link link-primary"><?php echo htmlspecialchars($post['title']); ?></a></td>
                        <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($post['username']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>
                        <td>
                            <?php if ($post['pinned']): ?>
                                <span class="badge badge-info">ปักหมุด</span>
                            <?php endif; ?>
                            <?php if ($post['hidden']): ?>
                                <span class="badge badge-warning">ซ่อน</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="document.getElementById('toggle_pinned_<?php echo $post['id']; ?>').showModal()">
                                <?php echo $post['pinned'] ? 'ยกเลิกปักหมุด' : 'ปักหมุด'; ?>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="document.getElementById('toggle_hidden_<?php echo $post['id']; ?>').showModal()">
                                <?php echo $post['hidden'] ? 'ยกเลิกซ่อน' : 'ซ่อน'; ?>
                            </button>
                            <!-- Modal สำหรับปักหมุด/ยกเลิกปักหมุด -->
                            <dialog id="toggle_pinned_<?php echo $post['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg"><?php echo $post['pinned'] ? 'ยกเลิกปักหมุด' : 'ปักหมุด'; ?>กระทู้</h3>
                                    <p class="py-4">คุณต้องการ<?php echo $post['pinned'] ? 'ยกเลิกการปักหมุด' : 'ปักหมุด'; ?>กระทู้ "<?php echo htmlspecialchars($post['title']); ?>" หรือไม่?</p>
                                    <form method="POST">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <div class="modal-action">
                                            <button class="btn btn-primary" type="submit" name="toggle_pinned">ยืนยัน</button>
                                            <button class="btn" type="button" onclick="document.getElementById('toggle_pinned_<?php echo $post['id']; ?>').close()">ยกเลิก</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                            <!-- Modal สำหรับซ่อน/ยกเลิกซ่อน -->
                            <dialog id="toggle_hidden_<?php echo $post['id']; ?>" class="modal">
                                <div class="modal-box">
                                    <h3 class="font-bold text-lg"><?php echo $post['hidden'] ? 'ยกเลิกซ่อน' : 'ซ่อน'; ?>กระทู้</h3>
                                    <p class="py-4">คุณต้องการ<?php echo $post['hidden'] ? 'ยกเลิกการซ่อน' : 'ซ่อน'; ?>กระทู้ "<?php echo htmlspecialchars($post['title']); ?>" หรือไม่?</p>
                                    <form method="POST">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <div class="modal-action">
                                            <button class="btn btn-warning" type="submit" name="toggle_hidden">ยืนยัน</button>
                                            <button class="btn" type="button" onclick="document.getElementById('toggle_hidden_<?php echo $post['id']; ?>').close()">ยกเลิก</button>
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

</body>
</html>