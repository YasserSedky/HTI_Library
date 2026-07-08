<?php
$db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
$count=$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
if($count>0){ header('Location: login.php'); exit; }
$msg='';
if($_SERVER['REQUEST_METHOD']=='POST'){
  $uname=trim($_POST['username']);
  $pass=password_hash($_POST['password'],PASSWORD_BCRYPT);
  $email=trim($_POST['email']);
  $q=$db->prepare('INSERT INTO users(username,password,email) VALUES(?,?,?)');
  $q->execute([$uname,$pass,$email]);
  session_start(); $_SESSION['admin_id']=$db->lastInsertId(); $_SESSION['admin_username']=$uname;
  header('Location: dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل أول مدير - مكتبة HTI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { background:#f7faf7; font-family:'Cairo',sans-serif; }
    .register-box { max-width:390px; background:#fff; border-radius:18px; box-shadow:0 2px 18px #34c75935; margin:auto; margin-top:60px; padding:35px 30px; }
    .main-title{color:#34c759;font-weight:bold;}
  </style>
</head>
<body>
  <div class='register-box'>
    <h2 class="main-title text-center mb-2"><i class="fa fa-user-plus"></i> إنشاء أول مدير للمكتبة</h2>
    <?php if($msg): ?><div class="alert alert-danger text-center"> <?= $msg ?> </div><?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label>اسم المستخدم</label>
        <input type="text" name="username" class="form-control" required autofocus />
      </div>
      <div class="mb-3">
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" class="form-control" required />
      </div>
      <div class="mb-3">
        <label>كلمة المرور</label>
        <input type="password" name="password" class="form-control" required minlength="6" />
      </div>
      <button class="btn btn-success w-100" type="submit"><i class="fa fa-plus"></i> إنشاء</button>
      <a href="../index.php" class="btn btn-link text-secondary w-100 mt-1">العودة للرئيسية</a>
    </form>
  </div>
</body>
</html>
