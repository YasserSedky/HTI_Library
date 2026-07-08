<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: login.php');
$db=new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4','root','');
$msg = '';
// إضافة إشعار
if(isset($_POST['add_notif'])){
  $text = trim($_POST['text']);
  $book_id = intval($_POST['book_id'])?:NULL;
  $db->prepare('INSERT INTO notifications (text,book_id,created_at,is_read) VALUES (?,?,NOW(),0)')->execute([$text,$book_id]);
  $msg = 'تمت إضافة الإشعار!';
  header('Location: notifications.php?saved=1'); exit;
}
// حذف إشعار
if(isset($_GET['delete'])){
  $id=intval($_GET['delete']);
  $db->prepare('DELETE FROM notifications WHERE id=?')->execute([$id]);
  $msg='تم حذف الإشعار!';
  header('Location: notifications.php?deleted=1'); exit;
}
// تعيين كمقروء
if(isset($_GET['read'])){
  $id=intval($_GET['read']);
  $db->prepare('UPDATE notifications SET is_read=1 WHERE id=?')->execute([$id]);
  $msg='تم التعليم كمقروء!';
  header('Location: notifications.php?read=1'); exit;
}
$notifs = $db->query('SELECT n.*, b.title book_title FROM notifications n LEFT JOIN books b ON n.book_id=b.id ORDER BY n.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$books = $db->query('SELECT id, title FROM books')->fetchAll(PDO::FETCH_ASSOC);
if(isset($_GET['saved'])) $msg='تمت إضافة الإشعار!';
if(isset($_GET['deleted'])) $msg='تم حذف الإشعار!';
if(isset($_GET['read'])) $msg='تم التعليم كمقروء!';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الإشعارات - لوحة تحكم مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Cairo',sans-serif;background:#f7faf7;}
        .main-title{color:#34c759;font-weight:bold;}
        .stat-card{border-radius:13px;background:#fff;box-shadow:0 1px 8px #34c75915;}
        .notif-unread{background:#eafefa;}
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container">
    <span class="navbar-brand mb-0 h1"><i class="fa fa-bell"></i> إدارة الإشعارات</span>
    <a href="dashboard.php" class="btn btn-light mx-1">الرئيسية</a>
    <a href="logout.php" class="btn btn-outline-danger">تسجيل خروج</a>
  </div>
</nav>
<div class="container">
  <div class="stat-card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h4 class="main-title"><i class="fa fa-bell"></i> الإشعارات</h4>
      <a href="#" class="btn btn-success" onclick="showAddNotif()"><i class="fa fa-plus"></i> إشعار جديد</a>
    </div>
    <?php if($msg): ?><div class="alert alert-success"> <?= $msg ?> </div><?php endif; ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle bg-white">
        <thead class="table-success"><tr><th>#</th><th>النص</th><th>عن كتاب</th><th>تاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach($notifs as $n): ?>
          <tr class="<?=!$n['is_read']?'notif-unread':''?>">
            <td><?=$n['id']?></td>
            <td><?=htmlspecialchars($n['text'])?></td>
            <td><?=$n['book_title']?htmlspecialchars($n['book_title']):'-'?></td>
            <td><?=$n['created_at']?></td>
            <td><?=$n['is_read']?'<span class=\'text-success\'>مقروء</span>':'<span class=\'text-danger\'>غير مقروء</span>'?></td>
            <td>
              <?php if(!$n['is_read']): ?><a href="notifications.php?read=<?=$n['id']?>" class="btn btn-sm btn-success"><i class="fa fa-eye"></i></a><?php endif; ?>
              <a href="notifications.php?delete=<?=$n['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('هل تريد حذف الإشعار؟')"><i class="fa fa-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; if(!count($notifs)): ?>
          <tr><td colspan="6" class="text-center">لا يوجد إشعارات حالياً.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Modal add notif -->
  <div class="modal fade" id="notifModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">إضافة إشعار</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="add_notif" value="1" />
        <div class="mb-3"><label>نص الإشعار</label><textarea name="text" class="form-control" required></textarea></div>
        <div class="mb-3">
          <label>عن كتاب (اختياري)</label>
          <select class="form-select" name="book_id"><option value="">لا يوجد</option><?php foreach($books as $b): ?><option value="<?=$b['id']?>"> <?=htmlspecialchars($b['title'])?> </option><?php endforeach; ?></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">إضافة</button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
      </div>
    </form></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showAddNotif(){
  new bootstrap.Modal(document.getElementById('notifModal')).show();
}
</script>
</body>
</html>
