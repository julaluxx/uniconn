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
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// แก้ไขโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password'];

    // จัดการการอัปโหลดรูปโปรไฟล์
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        $profile_pic = uploadImage($_FILES['profile_pic']);
    }

    // อัปเดตข้อมูลผู้ใช้ในฐานข้อมูล
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
    $stmt->execute([$username, $email, $password, $profile_pic, $user_id]);

    // อัปเดต session เพื่อให้รูปโปรไฟล์ใหม่แสดงทันที
    $_SESSION['profile_pic'] = $profile_pic;

    // แสดงข้อความแจ้งเตือนว่าบันทึกสำเร็จ
    $message = "บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว";
}

?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">โปรไฟล์ของ <?php echo htmlspecialchars($user['username']); ?></h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'default.jpg'); ?>" class="w-32 h-32 rounded-full" />
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
                <div class="form-control mt-6">
                    <button class="btn btn-primary" type="submit">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <h3 class="text-xl font-bold mt-4">กระทู้ที่สร้าง</h3>
    <?php foreach ($posts as $post): ?>
        <div class="card bg-base-100 shadow mb-2">
            <div class="card-body">
                <a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                <p>วันที่: <?php echo $post['created_at']; ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>