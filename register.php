<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว";
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 flex items-center justify-center h-screen">
    <div class="card w-96 bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">สมัครสมาชิก</h2>
            <?php if (isset($error)): ?><p class="text-error"><?php echo $error; ?></p><?php endif; ?>
            <form method="POST">
                <div class="form-control">
                    <label class="label"><span class="label-text">ชื่อผู้ใช้</span></label>
                    <input type="text" name="username" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">อีเมล</span></label>
                    <input type="email" name="email" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">รหัสผ่าน</span></label>
                    <input type="password" name="password" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button class="btn btn-primary" type="submit">สมัคร</button>
                </div>
            </form>
            <p>มีบัญชีแล้ว? <a href="login.php" class="link link-primary">เข้าสู่ระบบ</a></p>
        </div>
    </div>
</body>
</html>