<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $content, $user_id, $category_id]);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>สร้างกระทู้ใหม่ - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="bg-base-100 p-4">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">สร้างกระทู้ใหม่</h2>
            <form method="POST">
                <div class="form-control">
                    <label class="label"><span class="label-text">หัวข้อ</span></label>
                    <input type="text" name="title" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">หมวดหมู่</span></label>
                    <select name="category_id" class="select select-bordered" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">เนื้อหา</span></label>
                    <textarea name="content" class="textarea textarea-bordered" required></textarea>
                </div>
                <div class="form-control mt-6">
                    <button class="btn btn-primary" type="submit">โพสต์</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>