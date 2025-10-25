<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// ดึงกระทู้ของผู้ใช้
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// ดึงความคิดเห็นของผู้ใช้
$stmt = $pdo->prepare("SELECT c.*, p.title as post_title FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$comments = $stmt->fetchAll();

// แก้ไขโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password'];

    // ตรวจสอบว่าอีเมลซ้ำหรือไม่
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $message = "เกิดข้อผิดพลาด: อีเมลนี้ถูกใช้แล้ว";
    } else {
        // จัดการการอัปโหลดรูปโปรไฟล์
        $profile_pic = $user['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
            $profile_pic = uploadImage($_FILES['profile_pic']);
            if (!$profile_pic) {
                $message = "เกิดข้อผิดพลาด: ไม่สามารถอัปโหลดรูปโปรไฟล์ได้";
            }
        }

        // อัปเดตข้อมูลผู้ใช้ในฐานข้อมูล
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([$username, $email, $password, $profile_pic, $user_id]);

            // อัปเดต session เพื่อให้รูปโปรไฟล์และชื่อผู้ใช้ใหม่แสดงทันที
            $_SESSION['profile_pic'] = $profile_pic;
            $_SESSION['username'] = $username;

            // แสดงข้อความแจ้งเตือนว่าบันทึกสำเร็จ
            $message = "บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <!-- เมนูนำทาง -->
    <div class="navbar bg-primary text-primary-content shadow-lg">
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

    <!-- แสดงรายละเอียดของโปรไฟล์ -->
    <div class="container mx-auto mt-6">
        <div class="card bg-base-100 shadow-xl p-4 mb-6">
            <div class="card-body">
                <h2 class="card-title text-2xl">โปรไฟล์ของ <?php echo htmlspecialchars($user['username']); ?></h2>
                <?php if (isset($message)): ?>
                    <div class="alert <?php echo strpos($message, 'ข้อผิดพลาด') === false ? 'alert-success' : 'alert-error'; ?> mb-4">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-4">
                    <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" class="w-24 h-24 rounded-full" />
                    <div>
                        <p><strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>บทบาท:</strong> <?php echo htmlspecialchars($user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : ($user['role'] == 'moderator' ? 'ผู้ดูแล' : 'สมาชิก')); ?></p>
                    </div>
                </div>
                <button class="btn btn-primary mt-4" onclick="document.getElementById('edit_profile_modal').showModal()">แก้ไขโปรไฟล์</button>
            </div>
        </div>

        <!-- Modal สำหรับแก้ไขโปรไฟล์ -->
        <dialog id="edit_profile_modal" class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg">แก้ไขโปรไฟล์</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-control">
                        <label class="label"><span class="label-text">ชื่อผู้ใช้</span></label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="input input-bordered" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">อีเมล</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="input input-bordered" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">รหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)</span></label>
                        <input type="password" name="password" class="input input-bordered" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">รูปโปรไฟล์</span></label>
                        <input type="file" name="profile_pic" class="file-input file-input-bordered" accept="image/*" />
                    </div>
                    <div class="modal-action">
                        <button type="submit" name="edit_profile" class="btn btn-primary">บันทึก</button>
                        <button type="button" class="btn" onclick="document.getElementById('edit_profile_modal').close()">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- กระทู้ที่สร้าง -->
        <h3 class="text-xl font-bold mt-8 mb-4">กระทู้ที่สร้าง</h3>
        <?php if (empty($posts)): ?>
            <p class="text-gray-500">คุณยังไม่ได้สร้างกระทู้</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card bg-base-100 shadow mb-2">
                    <div class="card-body">
                        <h4 class="font-bold"><a href="view_post.php?id=<?php echo $post['id']; ?>" class="link link-primary"><?php echo htmlspecialchars($post['title']); ?></a></h4>
                        <p>หมวด: <?php echo htmlspecialchars($post['category_name']); ?> | วันที่: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> | ดู: <?php echo $post['views']; ?></p>
                        <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . '...'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ความคิดเห็นที่สร้าง -->
        <h3 class="text-xl font-bold mt-8 mb-4">ความคิดเห็นที่สร้าง</h3>
        <?php if (empty($comments)): ?>
            <p class="text-gray-500">คุณยังไม่ได้แสดงความคิดเห็น</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="card bg-base-100 shadow mb-2">
                    <div class="card-body">
                        <p><strong>ในกระทู้:</strong> <a href="view_post.php?id=<?php echo $comment['post_id']; ?>" class="link link-primary"><?php echo htmlspecialchars($comment['post_title']); ?></a></p>
                        <p><?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . '...'; ?></p>
                        <p>วันที่: <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer p-10 bg-neutral text-neutral-content mt-8">
        <div>© UniConnect 2025 - สงวนลิขสิทธิ์</div>
    </footer>
</body>
</html>