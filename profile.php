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

    // ตรวจสอบอีเมลซ้ำ
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $message = "เกิดข้อผิดพลาด: อีเมลนี้ถูกใช้แล้ว";
    } else {
        $profile_pic = $user['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
            $profile_pic = uploadImage($_FILES['profile_pic']);
            if (!$profile_pic) {
                $message = "เกิดข้อผิดพลาด: ไม่สามารถอัปโหลดรูปโปรไฟล์ได้";
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([$username, $email, $password, $profile_pic, $user_id]);

            $_SESSION['profile_pic'] = $profile_pic;
            $_SESSION['username'] = $username;

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
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.css" rel="stylesheet" type="text/css" />
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
                    <a href="profile.php" class="btn btn-success hover:bg-primary-focus">โปรไฟล์</a>
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

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-6 py-8 max-w-5xl">
        <div class="card bg-base-100 shadow-2xl p-6 mb-8 transition-all hover:shadow-3xl">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold text-primary mb-4">👤 โปรไฟล์ของ
                    <?php echo htmlspecialchars($user['username']); ?></h2>

                <?php if (isset($message)): ?>
                    <div
                        class="alert <?php echo strpos($message, 'ข้อผิดพลาด') === false ? 'alert-success' : 'alert-error'; ?> mb-4">
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col md:flex-row items-center gap-6">
                    <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>"
                        class="w-32 h-32 rounded-full border-4 border-primary/20 shadow" />
                    <div>
                        <p><strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>บทบาท:</strong>
                            <?php echo htmlspecialchars($user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : ($user['role'] == 'moderator' ? 'ผู้ดูแล' : 'สมาชิก')); ?>
                        </p>
                    </div>
                </div>

                <button class="btn btn-primary mt-6"
                    onclick="document.getElementById('edit_profile_modal').showModal()">
                    ✏️ แก้ไขโปรไฟล์
                </button>
            </div>
        </div>

        <!-- Modal แก้ไขโปรไฟล์ -->
        <dialog id="edit_profile_modal" class="modal">
            <div class="modal-box max-w-md">
                <h3 class="font-bold text-lg mb-2">แก้ไขโปรไฟล์</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">ชื่อผู้ใช้</span></label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                            class="input input-bordered" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">อีเมล</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                            class="input input-bordered" required />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">รหัสผ่านใหม่
                                (เว้นว่างหากไม่เปลี่ยน)</span></label>
                        <input type="password" name="password" class="input input-bordered" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">รูปโปรไฟล์</span></label>
                        <input type="file" name="profile_pic" class="file-input file-input-bordered" accept="image/*" />
                    </div>
                    <div class="modal-action">
                        <button type="submit" name="edit_profile" class="btn btn-primary">บันทึก</button>
                        <button type="button" class="btn"
                            onclick="document.getElementById('edit_profile_modal').close()">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- กระทู้ที่สร้าง -->
        <section class="mt-10">
            <h3 class="text-xl font-bold text-primary mb-4">📚 กระทู้ที่คุณสร้าง</h3>
            <?php if (empty($posts)): ?>
                <p class="text-gray-500 italic">คุณยังไม่ได้สร้างกระทู้</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($posts as $post): ?>
                        <div class="card bg-base-100 shadow p-4 hover:shadow-md transition-all">
                            <h4 class="font-bold text-lg">
                                <a href="view_post.php?id=<?php echo $post['id']; ?>" class="link link-primary">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h4>
                            <p class="text-sm text-gray-500">
                                หมวด: <?php echo htmlspecialchars($post['category_name']); ?> •
                                วันที่: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> •
                                ดู: <?php echo $post['views']; ?>
                            </p>
                            <p class="text-gray-700 mt-1">
                                <?php echo htmlspecialchars(substr($post['content'], 0, 120)) . '...'; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- ความคิดเห็น -->
        <section class="mt-10">
            <h3 class="text-xl font-bold text-primary mb-4">💬 ความคิดเห็นของคุณ</h3>
            <?php if (empty($comments)): ?>
                <p class="text-gray-500 italic">คุณยังไม่ได้แสดงความคิดเห็น</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($comments as $comment): ?>
                        <div class="card bg-base-100 shadow p-4 hover:shadow-md transition-all">
                            <p><strong>ในกระทู้:</strong>
                                <a href="view_post.php?id=<?php echo $comment['post_id']; ?>" class="link link-primary">
                                    <?php echo htmlspecialchars($comment['post_title']); ?>
                                </a>
                            </p>
                            <p class="text-gray-700 mt-1">
                                <?php echo htmlspecialchars(substr($comment['content'], 0, 120)) . '...'; ?></p>
                            <p class="text-sm text-gray-500">วันที่:
                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content py-4 border-t border-base-300 mt-10">
        <p class="text-sm text-gray-600">© 2025 UniConnect — สังคมนักศึกษาออนไลน์</p>
    </footer>
</body>

</html>