<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        header('Location: index.php');
        exit;
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 flex items-center justify-center h-screen">
    <div class="card w-96 bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">เข้าสู่ระบบ</h2>
            <?php if (isset($error)): ?><p class="text-error"><?php echo $error; ?></p><?php endif; ?>
            <form method="POST">
                <div class="form-control">
                    <label class="label"><span class="label-text">อีเมล</span></label>
                    <input type="email" name="email" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">รหัสผ่าน</span></label>
                    <input type="password" name="password" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
                </div>
            </form>
            <p>ยังไม่มีบัญชี? <a href="register.php" class="link link-primary">สมัครสมาชิก</a></p>
        </div>
    </div>
</body>
</html>