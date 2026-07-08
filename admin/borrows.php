<?php
session_start();
if (!isset($_SESSION['admin_id'])) header('Location: login.php');
$db = new PDO('mysql:host=localhost;dbname=hti_library;charset=utf8mb4', 'root', '');
$msg = '';
// فلاتر البحث
$filter = [
    'book_title' => $_GET['book_title'] ?? '',
    'student_name' => $_GET['student_name'] ?? '',
    'student_code' => $_GET['student_code'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];
$where = [];
$params = [];
if($filter['book_title']) { $where[] = "b.title LIKE ?"; $params[] = "%{$filter['book_title']}%"; }
if($filter['student_name']) { $where[] = "br.student_name LIKE ?"; $params[] = "%{$filter['student_name']}%"; }
if($filter['student_code']) { $where[] = "br.student_code LIKE ?"; $params[] = "%{$filter['student_code']}%"; }
if($filter['status']) { $where[] = "br.status=?"; $params[] = $filter['status']; }
if($filter['date_from']) { $where[] = "br.date_borrowed >= ?"; $params[] = $filter['date_from']; }
if($filter['date_to']) { $where[] = "br.date_borrowed <= ?"; $params[] = $filter['date_to']; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
// إضافة استعارة
if (isset($_POST['add_borrow'])) {
    $book_id = intval($_POST['book_id']);
    $return_due = $_POST['return_due'];
    $student_name = trim($_POST['student_name']);
    $student_code = trim($_POST['student_code']);
    // تحقق من توفر نسخ
    $stock = $db->prepare('SELECT copies FROM books WHERE id=?');
    $stock->execute([$book_id]);
    $copies = (int)$stock->fetchColumn();
    if ($copies <= 0) {
        $msg = 'لا توجد نسخ متاحة لهذا الكتاب!';
    } else {
        $db->prepare('INSERT INTO borrows (user_id,book_id,student_name,student_code,status,date_borrowed,return_due) VALUES (?,?,?,?,?,?,?)')->execute([
            $_SESSION['admin_id'],
            $book_id,
            $student_name,
            $student_code,
            'borrowed',
            date('Y-m-d'),
            $return_due
        ]);
        // تحديث نسخ الكتاب
        $db->prepare('UPDATE books SET copies=copies-1 WHERE id=?')->execute([$book_id]);
        $msg = 'تمت إضافة الاستعارة!';
        header('Location: borrows.php?added=1');
        exit;
    }
}
// تغيير الحالة والحذف 
if (isset($_GET['set_returned'])) {
    $id = intval($_GET['set_returned']);
    // استخرج id الكتاب
    $q = $db->prepare('SELECT book_id FROM borrows WHERE id=?');
    $q->execute([$id]);
    $book_id = $q->fetchColumn();
    $db->prepare("UPDATE borrows SET status='returned', date_returned=? WHERE id=?")->execute([date('Y-m-d'), $id]);
    $db->prepare('UPDATE books SET copies=copies+1 WHERE id=?')->execute([$book_id]);
    $msg = 'تم تحديث حالة الاستعارة!';
    header('Location: borrows.php?returned=1');
    exit;
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->prepare('DELETE FROM borrows WHERE id=?')->execute([$id]);
    $msg = 'تم حذف الاستعارة!';
    header('Location: borrows.php?deleted=1');
    exit;
}
$books = $db->query('SELECT id,title FROM books')->fetchAll(PDO::FETCH_ASSOC);
$sql = "SELECT br.*, b.title as book_title, u.username as admin FROM borrows br LEFT JOIN books b ON br.book_id=b.id LEFT JOIN users u ON br.user_id=u.id $whereSql ORDER BY br.id DESC";
$st = $db->prepare($sql);
$st->execute($params);
$borrows = $st->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['added'])) $msg = 'تمت إضافة الاستعارة!';
if (isset($_GET['returned'])) $msg = 'تم تحديث حالة الاستعارة!';
if (isset($_GET['deleted'])) $msg = 'تم حذف الاستعارة!';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الاستعارات - لوحة تحكم مكتبة HTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Cairo',sans-serif;background:#f7faf7;}
        .stat-card{border-radius:13px;background:#fff;box-shadow:0 1px 8px #34c75915;}
        .main-title{color:#34c759;font-weight:bold;}
        .status-r{color:#34c759;font-weight:bold;}
        .status-b{color:#111;}
        .status-l{color:#ff9040;font-weight:bold;}
        .filter-bar-admin{background:#eafefa; border-radius:13px; box-shadow:0 2px 7px #34c7590d; margin-bottom:18px; padding:22px 15px;}
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container">
    <span class="navbar-brand mb-0 h1"><i class="fa fa-hand-holding"></i> إدارة الاستعارات</span>
    <a href="dashboard.php" class="btn btn-light mx-1">الرئيسية</a>
    <a href="logout.php" class="btn btn-outline-danger">تسجيل خروج</a>
  </div>
</nav>
<div class="container">
  <div class="stat-card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h4 class="main-title"><i class="fa fa-hand-holding"></i> الاستعارات</h4>
      <a href="#" class="btn btn-success" onclick="showAddBorrow()"><i class="fa fa-plus"></i> استعارة جديدة</a>
    </div>
    <!-- فلاتر البحث المتقدمة -->
    <form class="filter-bar-admin row g-2 mb-3" method="get" action="">
      <div class="col-md-2">
        <input type="text" class="form-control" name="book_title" value="<?=$filter['book_title']?>" placeholder="بحث بالكتاب" />
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" name="student_name" value="<?=$filter['student_name']?>" placeholder="اسم الطالب" />
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" name="student_code" value="<?=$filter['student_code']?>" placeholder="كود الطالب" />
      </div>
      <div class="col-md-2">
        <select class="form-select" name="status">
          <option value="">كل الحالات</option>
          <option value="borrowed" <?=($filter['status']=='borrowed'?'selected':'')?>>مستعارة</option>
          <option value="returned" <?=($filter['status']=='returned'?'selected':'')?>>أعيدت</option>
          <option value="late" <?=($filter['status']=='late'?'selected':'')?>>متأخرة</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" name="date_from" value="<?=$filter['date_from']?>" placeholder="من" />
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" name="date_to" value="<?=$filter['date_to']?>" placeholder="إلى" />
      </div>
      <div class="col-md-12 text-end">
        <button class="btn btn-info px-5 mt-2" type="submit"><i class="fa fa-filter"></i> تطبيق الفلاتر</button>
        <a href="borrows.php" class="btn btn-light mt-2">إزالة الفلاتر</a>
      </div>
    </form>
    <?php if ($msg): ?><div class="alert alert-success"> <?= $msg ?> </div><?php endif; ?>
    <div class="table-responsive">
                <table class="table table-bordered align-middle bg-white">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>اسم الكتاب</th>
                            <th>اسم الطالب</th>
                            <th>كود الطالب</th>
                            <th>المدير المستعير</th>
                            <th>تاريخ الاستعارة</th>
                            <th>تاريخ الإرجاع</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrows as $br): ?>
                            <tr>
                                <td><?= $br['id'] ?></td>
                                <td><?= htmlspecialchars($br['book_title']) ?></td>
                                <td><?= htmlspecialchars($br['student_name']) ?></td>
                                <td><?= htmlspecialchars($br['student_code']) ?></td>
                                <td><?= htmlspecialchars($br['admin']) ?></td>
                                <td><?= $br['date_borrowed'] ?></td>
                                <td><?= $br['return_due'] ?></td>
                                <td>
                                    <?php if ($br['status'] === 'returned'): ?>
                                        <span class="status-r"><i class="fa fa-check"></i> أعيدت</span>
                                    <?php elseif ($br['status'] === 'late'): ?>
                                        <span class="status-l"><i class="fa fa-clock"></i> متأخرة</span>
                                    <?php else: ?>
                                        <span class="status-b"><i class="fa fa-book"></i> مستعارة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($br['status'] !== 'returned'): ?>
                                        <a href="borrows.php?set_returned=<?= $br['id'] ?>" class="btn btn-sm btn-success"><i class="fa fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="borrows.php?delete=<?= $br['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('تأكيد حذف الاستعارة؟')"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach;
                        if (!count($borrows)): ?>
                            <tr>
                                <td colspan="9" class="text-center">لا يوجد استعارات حالياً.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal add borrow -->
        <div class="modal fade" id="borrowModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة استعارة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_borrow" value="1" />
                        <div class="mb-3">
                            <label>اختر كتاب</label>
                            <select class="form-select" name="book_id" required>
                                <option value="">اختر...</option><?php foreach ($books as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>اسم الطالب</label>
                            <input type="text" name="student_name" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label>كود الطالب</label>
                            <input type="text" name="student_code" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label>موعد الاستحقاق (الإرجاع)</label>
                            <input type="date" name="return_due" class="form-control" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">إضافة</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAddBorrow() {
            new bootstrap.Modal(document.getElementById('borrowModal')).show();
        }
    </script>
</body>

</html>