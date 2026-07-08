<?php
session_start();
if(isset($_SESSION['admin_id'])) header('Location: dashboard.php');
$msg = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
  $db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
  $q=$db->prepare('SELECT * FROM users WHERE username=?');
  $q->execute([$_POST['username']]);
  $user=$q->fetch(PDO::FETCH_ASSOC);
  if($user && password_verify($_POST['password'],$user['password'])){
    $_SESSION['admin_id']=$user['id'];
    $_SESSION['admin_username']=$user['username'];
    header('Location: dashboard.php'); exit;
  }else{
    $msg = 'بيانات الدخول غير صحيحة!';
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الإدارة - مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f7faf7; font-family:'Cairo',sans-serif; }
        .login-box {
          max-width:380px;background:#fff;border-radius:18px;box-shadow:0 2px 18px #34c75935;margin:auto;margin-top:70px;padding:35px 30px;
        }
        .main-title{color:#34c759;font-weight:bold;}
    </style>
</head>
<body>
  <div class='login-box'>
    <h2 class="main-title text-center mb-3"><i class="fa fa-lock"></i> دخول الإدارة</h2>
    <?php if($msg): ?><div class="alert alert-danger text-center"> <?= $msg ?> </div><?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="form-group mb-3">
        <label>اسم المستخدم</label>
        <input type="text" name="username" class="form-control" required autofocus />
      </div>
      <div class="form-group mb-4">
        <label>كلمة المرور </label>
        <input type="password" name="password" class="form-control" required />
      </div>
      <button class="btn btn-success w-100" type="submit"><i class="fa fa-key"></i> دخول</button>
      <a href="../index.php" class="btn btn-link text-secondary w-100 mt-1">العودة للرئيسية</a>
    </form>
  </div>
</body>
</html>
